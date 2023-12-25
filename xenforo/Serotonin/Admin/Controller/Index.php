<?php

namespace Xyzt\Serotonin\Admin\Controller;

use XF\Mvc\ParameterBag;
use XF\Repository\Reaction;

use Xyzt\Serotonin\DB\MyBBLikePlugin;
use Xyzt\Serotonin\DB\MyBBImportLog;

class Index extends \XF\Admin\Controller\AbstractController
{   
    protected Reaction $reactionRepo;
    protected MyBBLikePlugin $myBBLikePlugin;
    protected MyBBImportLog $myBBImportLog;

    public function __construct(...$args)
    {
        parent::__construct(...$args);

        $this->reactionRepo = \XF::em()->getRepository('XF:Reaction');

        $this->myBBLikePlugin = new MyBBLikePlugin(
            \XF::options()->myBBLikePluginTableName
        );

        $this->myBBImportLog = new MyBBImportLog(
            \XF::options()->myBBImportLogTableName
        );
    }

    protected function preDispatchController($action, ParameterBag $params)
    {
        $this->assertAdminPermission('reactions');
    }

    public function actionIndex()
    {
        return $this->view(
            'Xyzt\Serotonin:Index',
            'xyzt_serotonin_index',
            [
                'warnings' => [
                    'La base de données sera modifiée. Assuez vous d\'avoir ' .
                    'une sauvegarde disponible.',
                    'Le script s\'exécute de manière synchrone et bloque le ' .
                    'serveur pendant un certain temps.',
                    'Il peut être nécessaire de régler la configuration de ' .
                    'PHP `max_execution_time` et les paramètres du ' .
                    'serveur web (`fastcgi_read_timeout` pour nginx).'
                ]
            ]
        );
    }

    public function actionImport()
    {
        ini_set('max_execution_time', '300');  // 5 minutes

        $myBBLikes = $this->myBBLikePlugin->getMyBBLikes();

        $errors = array();
        $reactions = array();

        foreach ($myBBLikes as $myBBLike) {
            $postId = $this->myBBImportLog->getNewPostId($myBBLike['pid']);
            $userId = $this->myBBImportLog->getNewUserId($myBBLike['uid']);

            $post = \XF::em()->find('XF:Post', $postId);
            $user = \XF::em()->find('XF:User', $userId);
            
            if ($post === null) {
                $errors[] = "Unable to fetch post with ID $postId";
                continue;
            }

            if ($user === null) {
                $errors[] = "Unable to fetch user with ID $postId";
                continue;
            }

            $reactions[] = $this->reactionRepo->insertReaction(
                reactionId: 1,
                contentType: $post->getEntityContentType(),
                contentId: $postId,
                reactUser: $user,
                publish: false,
                // It seems post content type has no handler for the bare like
                //reaction
                isLike: false,  
            );
        }

        $this->reactionRepo->rebuildReactionCache();

        return $this->view(
            'Xyzt\Serotonin:Result',
            'xyzt_serotonin_result',
            [
                'reactions' => $reactions,
                'errors' => $errors,
            ]
        );
    }
}
