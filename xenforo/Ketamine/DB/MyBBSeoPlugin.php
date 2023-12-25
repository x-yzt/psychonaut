<?php

namespace Xyzt\Ketamine\DB;

use XF\Db\AbstractAdapter;

class MyBBSeoPlugin
{
    protected AbstractAdapter $db;
    protected string $table_name;

    public function __construct(string $table_name)
    {
        $this->db = \XF::db();
        $this->table_name = $table_name;
    }

    public function getMyBBThreadIdFromSlug(string $slug) : int
    {
        return $this->db->fetchOne("
            SELECT `id`
            FROM $this->table_name
            WHERE `url` = ?
                AND `idtype` = 4
                AND `active` = 1;
        ", $slug);
    }
}
