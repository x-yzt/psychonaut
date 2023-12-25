<?php

namespace Xyzt\Serotonin\DB;

use XF\Db\AbstractAdapter;

class MyBBLikePlugin
{
    protected AbstractAdapter $db;
    protected string $table_name;

    public function __construct(string $table_name)
    {
        $this->db = \XF::db();
        $this->table_name = $table_name;
    }

    public function getMyBBLikes() : array
    {
        return $this->db->fetchAll("
            SELECT `pid`, `uid`, `dateline`
            FROM $this->table_name
            ORDER BY `tlid` DESC;
        ");
    }
}
