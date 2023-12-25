<?php

namespace Xyzt\Ketamine\Pub\Controller;

use XF\Mvc\ParameterBag;
use Xyzt\Ketamine\DB\MyBBSeoPlugin;
use Xyzt\Ketamine\DB\MyBBImportLogs;

class MyBBThread extends \XF\Pub\Controller\AbstractController
{   
    protected MyBBSeoPlugin $mybb_seo_plugin;
    protected MyBBImportLogs $mybb_import_logs;

    public function __construct(...$args)
    {
        parent::__construct(...$args);

        $this->mybb_seo_plugin = new MyBBSeoPlugin(
            \XF::options()->mybb_seo_plugin_table_name
        );

        $this->mybb_import_logs = new MyBBImportLogs(
            \XF::options()->mybb_import_log_table_name
        );
    }

    public function actionIndex(ParameterBag $params)
    {   
        $mybb_tid = $this
            ->mybb_seo_plugin
            ->getMyBBThreadIdFromSlug(trim($params->thread_slug, "/\\"));

        $xf_tid = $this
            ->mybb_import_logs
            ->getXFThreadIdFromMyBBThreadId($mybb_tid);

        $thread = \XF::em()->find('XF:Thread', $xf_tid);

        $redirect_url = \XF::app()
            ->router('public')
            ->buildLink('threads', $thread);

        return $this->redirectPermanently($redirect_url);
    }
}
