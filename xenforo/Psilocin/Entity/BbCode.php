<?php

namespace Xyzt\Psilocin\Entity;

use XF\Mvc\Entity\Structure;

class BbCode extends XFCP_BbCode
{
    public static function getStructure(Structure $structure) {
        $structure = parent::getStructure($structure);
        
        $structure->columns['bb_code_id']['match'] = [
            'alphanumeric_hyphen', // This is what is actually overriden
            'please_enter_bb_code_tag_using_only_alphanumeric_underscore'
        ];

        // Debug stuff
        unset($structure->columns['bb_code_id']['match']);
        \XF::dump($structure);

        return $structure;
	}
}
