<?php

namespace Xyzt\Serotonin\DB;

use XF\Db\AbstractAdapter;

class MyBBImportLog
{
    protected AbstractAdapter $db;
    protected string $table_name;

    public function __construct(string $table_name)
    {
        $this->db = \XF::db();
        $this->table_name = $table_name;
    }

    public function getNewPostId(int $id) : int
    {
        return $this->db->fetchOne("
            SELECT `new_id`
            FROM $this->table_name
            WHERE `content_type` = 'post'
                AND `old_id` = ?;
        ", $id);
    }

    public function getNewUserId(int $id) : int
    {
        return $this->db->fetchOne("
            SELECT `new_id`
            FROM $this->table_name
            WHERE `content_type` = 'user'
                AND `old_id` = ?;
        ", $id);
    }
}
