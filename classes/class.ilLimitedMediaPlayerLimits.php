<?php
/**
 * Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv3, see docs/LICENSE
 */

/**
 * Adaptation of playing limits in tests
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @version $Id$
 */
class ilLimitedMediaPlayerLimits
{
	private $parent_id;
	private $page_id;
	private $mob_id;
	private $user_id;

    /**
     * Limit definitions in order of relevance (most relevant first)
     * @var array
     */
	private $limits = array(
	    'one_user_one_medium' => null,
        'one_user_all_media' => null,
        'all_user_one_medium' => null,
        'all_user_all_media' => null,
    );

	/**
	 * Constructor
     * 	@param	int		$parent_id  obj_id of the parent objct
	 * @param	int		$page_id    id of the content page
	 * @param	int		$mob_id     id of the media object
	 * @param	int		$user_id    id of the user
	 */
	public function __construct($parent_id, $page_id = 0, $mob_id = 0, $user_id = 0)
	{
        $this->parent_id = (int) $parent_id;
		$this->page_id = (int) $page_id;
		$this->mob_id = (int) $mob_id;
		$this->user_id = (int) $user_id;

		// get the stored data
		$this->read();
	}

    /**
     * Get the key for the defined limit
     * @return string
     */
	private function getLimitKey()
    {
        switch (true)
        {
            case $this->user_id > 0 && $this->mob_id > 0:
                return 'one_user_one_medium';

            case $this->user_id > 0 && $this->mob_id == 0:
                return 'one_user_all_media';

            case $this->user_id == 0 && $this->mob_id > 0:
                return 'all_user_one_medium';

            case $this->user_id == 0 && $this->mob_id == 0:
                return 'all_user_all_media';
        }
    }

    /**
     * Set the limit that is defined for the given user and medium
     * @param   int|null    $a_limit
     */
    public function setLimit($a_limit = null)
    {
        $this->limits[$this->getLimitKey()] = $a_limit;

        if (isset($a_limit))
        {
            $this->write();
        }
        else
        {
            $this->delete();
        }
    }


    /**
     * Get the Limit that is defined for the given user and medium
     */
	public function getLimit()
    {
        return $this->limits[$this->getLimitKey()];
    }


    /**
     * Get the effective limit for the given user and medium
     * @param int $a_default_limit
     */
    public function getEffectiveLimit($a_default_limit = 0)
    {
        foreach ($this->limits as $key => $limit)
        {
            if (isset($limit))
            {
                return $limit;
            }
        }
        return $a_default_limit;
    }

	
	/**
	 * read data from storage
	 */
	private function read()
	{
		global $ilDB;
		
        $query = "SELECT * FROM copg_pgcp_limply_limits "
        . " WHERE parent_id = " . $ilDB->quote($this->parent_id, 'integer')
        . " AND (page_id = 0 OR page_id = " . $ilDB->quote($this->page_id, 'integer') . ")"
        . " AND (mob_id = 0 OR mob_id = " . $ilDB->quote($this->mob_id, 'integer'). ")"
        . " AND (user_id = 0 OR user_id = " . $ilDB->quote($this->user_id, 'integer') . ")";
        $res = $ilDB->query($query);

        while ($row = $ilDB->fetchAssoc($res))
        {
            switch (true)
            {
                case $row['user_id'] > 0 && $row['mob_id'] > 0:
                    $this->limits['one_user_one_medium'] = $row['limit_plays'];
                    break;

                case $row['user_id'] > 0 && $row['mob_id'] == 0:
                    $this->limits['one_user_all_media'] = $row['limit_plays'];
                    break;

                case $row['user_id'] == 0 && $row['mob_id'] > 0:
                    $this->limits['all_user_one_medium'] = $row['limit_plays'];
                    break;

                case $row['user_id'] == 0 && $row['mob_id'] == 0:
                    $this->limits['all_user_all_media'] = $row['limit_plays'];
            }
        }
    }
	
	/**
	 * write data to the storage
	 */
	private function write()
	{
		global $ilDB;
		
        $ilDB->replace('copg_pgcp_limply_limits',
            array(
                'parent_id' => array('integer', $this->parent_id),
                'page_id' => array('integer', $this->page_id),
                'mob_id' => array('integer', $this->mob_id),
                'user_id' => array('integer', $this->user_id),
            ),
            array(
                'limit_plays' => array('integer', $this->plays),
            )
        );
	}


    /**
     * delete data in storage
     */
	private function delete()
    {
        global $ilDB;

        $query = "DELETE FROM copg_pgcp_limply_limits "
            . " WHERE parent_id = " . $ilDB->quote($this->parent_id, 'integer')
            . " AND page_id = " . $ilDB->quote($this->page_id, 'integer')
            . " AND mob_id = " . $ilDB->quote($this->mob_id, 'integer')
            . " AND user_id = " . $ilDB->quote($this->user_id, 'integer');

        $ilDB->manipulate($query);
    }
}