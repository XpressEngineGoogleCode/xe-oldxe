<?php
    /**
     * @class  kinAdminController
     * @author NHN (developers@xpressengine.com)
     * @brief  kin admin controller class
     **/

    class kinAdminController extends kin {

        function init() {
        }

        function procKinAdminInsert() {
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');

            $args = Context::getRequestVars();
            $args->module = 'kin';
            $args->mid = $args->kin_name;
            unset($args->body);
            unset($args->kin_name);

            if($args->use_category!='Y') $args->use_category = 'N';
            if($args->limit_write !='Y') $args->limit_write = 'N';
            if(!$args->limit_give_point) $args->limit_give_point = 100;

            if($args->module_srl) {
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
                if($module_info->module_srl != $args->module_srl) unset($args->module_srl);
            }

            if(!$args->module_srl) {
                $output = $oModuleController->insertModule($args);
                $msg_code = 'success_registed';
            } else {
                $output = $oModuleController->updateModule($args);
                $msg_code = 'success_updated';
            }

            if(!$output->toBool()) return $output;

            $this->setRedirectUrl(getUrl('','module','admin','act','dispKinAdminInsert','module_srl',$output->get('module_srl')));
            $this->setMessage($msg_code);
        }

        function procKinAdminDelete() {
            $oModuleController = &getController('module');

            $module_srl = Context::get('module_srl');
            $output = $oModuleController->deleteModule($module_srl);
            if(!$output->toBool()) return $output;

            $args->module_srl = $module_srl;
            executeQuery('kin.deleteReplies', $args);

            $this->setRedirectUrl(getUrl('','module','admin','act','dispKinAdminList'));
            $this->setMessage('success_deleted');
        }

    }
?>
