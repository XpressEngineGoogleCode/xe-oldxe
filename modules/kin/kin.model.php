<?php
    /**
     * @class  kinModel
     * @author NHN (developers@xpressengine.com)
     * @brief  kin model class
     **/

    class kinModel extends kin {

        function init() {
        }

        function getNotRepliedQuestions($module_srl, $category_srl = null, $list_count = 20, $page = 1, $search_keyword = null) {
            $oDocumentModel = &getModel('document');
            
            $args->module_srl = $module_srl;
            if(!is_null($category_srl)) $args->category_srl = $category_srl;
            $args->sort_index = 'doc.list_order';
            $args->order_type = 'asc';
            $args->page = $page;
            $args->list_count = $list_count;
            $args->page_count = 10;
            $args->comment_count = 0;
            if(!is_null($search_keyword)) $args->search_keyword = str_replace(' ','%',$search_keyword);
            $output = executeQueryArray('kin.getNotRepliedQuestions', $args);
            return $this->_arrangeDocument($output);
        }

        function getMyReplies($module_srl, $member_srl, $category_srl = null, $list_count = 20, $page = 1, $search_keyword = null) {
            $oCommentModel = &getModel('comment');
            
            $args->module_srl = $module_srl;
            if(!is_null($category_srl)) $args->category_srl = $category_srl;
            $args->sort_index = 'doc.list_order';
            $args->order_type = 'asc';
            $args->page = $page;
            $args->list_count = $list_count;
            $args->page_count = 10;
            $args->comment_count = 0;
            $args->member_srl = $member_srl;
            if(!is_null($search_keyword)) $args->search_keyword = str_replace(' ','%',$search_keyword);
            $output = executeQueryArray('kin.getMyReplies', $args);
            if(!$output->data) return array();
            foreach($output->data as $key => $val) {
                unset($oComment);
                $oComment = $oCommentModel->getComment(0);
                $oComment->setAttribute($val);
                $output->data[$key] = $oComment;

            }
            return $output;
        }

        function getNotSelectedReplies($module_srl, $category_srl = null, $list_count = 20, $page = 1, $search_keyword = null) {
            $oCommentModel = &getModel('comment');

            $args->module_srl = $module_srl;
            if(!is_null($category_srl)) $args->category_srl = $category_srl;
            $args->list_count = $list_count;
            $args->page = $page;
            $args->sort_index = 'reply.list_order';
            $args->order_type = 'asc';
            $args->page_count = 10;
            if(!is_null($search_keyword)) $args->search_keyword = str_replace(' ','%',$search_keyword);
            $output = executeQueryArray('kin.getNotSelectedReplies', $args);
            if($output->data) {
                foreach($output->data as $key => $val) {
                    $oCommentItem = new commentItem();
                    $oCommentItem->setAttribute($val);

                    // 권한이 있는 글에 대해 임시로 권한이 있음을 설정
                    if($oCommentItem->isGranted()) $accessible[$val->comment_srl] = true;

                    // 현재 댓글이 비밀글이고 부모글이 있는 답글이고 부모글에 대해 관리 권한이 있으면 보기 가능하도록 수정
                    if($val->parent_srl>0 && $val->is_secret == 'Y' && !$oCommentItem->isAccessible() && $accessible[$val->parent_srl]===true) {
                        $oCommentItem->setAccessible();
                    }
                    $output->data[$key] = $oCommentItem;
                }
            }
            return $output;
        }

        function getSelectedQuestions($module_srl, $category_srl = null, $list_count = 20, $page = 1, $search_keyword = null) {
            $oDocumentModel = &getModel('document');
            
            $args->module_srl = $module_srl;
            if(!is_null($category_srl)) $args->category_srl = $category_srl;
            $args->sort_index = 'list_order';
            $args->order_type = 'asc';
            $args->page = $page;
            $args->list_count = $list_count;
            $args->comment_count = 0;
            $args->page_count = 10;
            if(!is_null($search_keyword)) $args->search_keyword = str_replace(' ','%',$search_keyword);
            $output = executeQueryArray('kin.getSelectedQuestions', $args);
            return $this->_arrangeDocument($output);
        }

        function getMyQuestions($module_srl, $category_srl = null, $member_srl, $list_count = 20, $page = 1, $search_keyword = null) {
            $oDocumentModel = &getModel('document');
            
            $args->module_srl = $module_srl;
            if(!is_null($category_srl)) $args->category_srl = $category_srl;
            $args->sort_index = 'list_order';
            $args->order_type = 'asc';
            $args->page = $page;
            $args->list_count = $list_count;
            $args->member_srl = $member_srl;
            $args->page_count = 10;
            if(!is_null($search_keyword)) $args->search_keyword = str_replace(' ','%',$search_keyword);
            $output = executeQueryArray('kin.getMyQuestions', $args);
            return $this->_arrangeDocument($output);
        }

        function _arrangeDocument($output) {
            $oDocumentModel = &getModel('document');

            if($output->data) {
                foreach($output->data as $key => $attribute) {
                    $document_srl = $attribute->document_srl;
                    if(!$GLOBALS['XE_DOCUMENT_LIST'][$document_srl]) {
                        $oDocument = null;
                        $oDocument = new documentItem();
                        $oDocument->setAttribute($attribute, false);
                        $GLOBALS['XE_DOCUMENT_LIST'][$document_srl] = $oDocument;
                    }
                    $output->data[$key] = $GLOBALS['XE_DOCUMENT_LIST'][$document_srl];
                }
            }
            $oDocumentModel->setToAllDocumentExtraVars();
            return $output;
        }

        function getKinPoint($document_srl) {
            $args->document_srl = $document_srl;
            $output = executeQuery('kin.getKinPoint', $args);
            return $output->data->point;
        }

        function getSelectedReply($document_srl) {
            $args->document_srl = $document_srl;
            $output = executeQuery('kin.getKinThread', $args);
            return $output->data->selected;
        }

        function getKinCommentCount($parent_srls = array()) {
            if(!count($parent_srls)) return array();

            $args->parent_srl = implode(',',$parent_srls);
            $output = executeQueryArray('kin.getReplyCount', $args);
            if(!$output->data) return array();
            foreach($output->data as $key => $val) $result[$val->parent_srl] = $val->count;
            return $result;
        }

        function getKinComments() {
            if(!$this->module_srl) return new Object(-1,'msg_invalid_request');

            $parent_srl = Context::get('parent_srl');
            $page = Context::get('page');
            $output = $this->getKinCommentList($this->module_info, $parent_srl, $page);
            $this->add('parent_srl', $output->get('parent_srl'));
            $this->add('html', $output->get('html'));
        }

        function getKinCommentList($module_info, $parent_srl, $page=1) {
            $args->module_srl = $module_info->module_srl;
            $args->parent_srl = $parent_srl;
            $args->page = $page;
            $output = executeQueryArray('kin.getReplies', $args);
            Context::set('comment_list', $output->data);
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('page_navigation', $output->page_navigation);

			$module_path = './modules/'.$module_info->module.'/';
			if(Mobile::isFromMobilePhone()) {
				$skin_path = $module_path.'m.skins/'.$module_info->mskin.'/';
				if(!$module_info->mskin || !is_dir($skin_path)) $skin_path = $module_path.'m.skins/default/';
			} else {
				$skin_path = $module_path.'skins/'.$module_info->skin.'/';
				if(!$module_info->skin || !is_dir($skin_path)) $skin_path = $module_path.'skins/xe_official/';
			}
			
            $oTemplateHandler = &TemplateHandler::getInstance();
            $result = new Object();
            $result->add('html', $oTemplateHandler->compile($skin_path, 'include.comments.html'));
            $result->add('parent_srl', $args->parent_srl);
            return $result;
        }
    }
?>
