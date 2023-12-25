<?php

namespace Xyzt\Ketamine\DB;

use XF\Db\AbstractAdapter;

class MyBBImportLogs
{
    protected AbstractAdapter $db;
    protected string $table_name;

    public function __construct(string $table_name)
    {
        $this->db = \XF::db();
        $this->table_name = $table_name;
    }

    public function getXFThreadIdFromMyBBThreadId(int $id) : int
    {
        return $this->db->fetchOne("
            SELECT `new_id`
            FROM $this->table_name
            WHERE `content_type` = 'thread'
                AND `old_id` = ?;
        ", $id);
    }
}
