<?php

class Rest_ShowController extends Zend_Rest_Controller
{
    public function init()
    {
        /* Initialize action controller here */
        /*
        $context = $this->_helper->getHelper('contextSwitch');
        $context->initContext();
        */

        /*
        $front     = Zend_Controller_Front::getInstance();
        $restRoute = new Zend_Rest_Route($front);
        $front->getRouter()->addRoute('show', $restRoute);
        */
        
        $this->_helper->layout->disableLayout();
    }

    public function indexAction()
    {
        if (!$this->verifyApiKey()) {
            return;
        }
         $this->getResponse()
            ->appendBody("From indexAction() returning all shows");
    }
    public function getAction()
    {
        if (!$this->verifyApiKey()) {
            return;
        }
        $id = $this->getId();
        if (!$id) {
            return;
        }

        $show = Airtime\CcShowQuery::create()->findPk($id);
        if ($show) {
            $this->getResponse()
                ->appendBody($show->exportTo("JSON"));
        } else {
            $this->showNotFoundResponse();
        }
    }
    
    public function postAction()
    {
    	$isUpdate = false;
    	
        if (!$this->verifyApiKey()) {
            return;
        }

        if ($id = $this->_getParam('id', false)) {
        	$isUpdate = true;
            //$resp = $this->getResponse();
            //$resp->setHttpResponseCode(400);
            //$resp->appendBody("ERROR: ID should not be specified when using POST. POST is only used for show creation, and an ID will be chosen by Airtime"); 
        }

        if (!$isUpdate)
        {
	        $show = new Airtime\CcShow(); 
	        $rawRequestBody = $this->getRequest()->getRawBody();
	        //Hacky check to see if the request is a whole JSON object (updating a Show)
	        if ($rawRequestBody[0] == '{') {
	            $show->importFrom('JSON', $rawRequestBody);
	            //Only update an existing show object if we were passed an id!
	            if ($show->getDbId() > 0)
	            {
	            	$show->save();
	            	return;    
	            }
	        }
        }

        //Otherwise, we're assuming this is a request to create a new show, using
        //URL parameters passed to us.
        //
        //TODO: Implement creation!!
        //TODO: Create a show
        //TODO: Then create a show instance?
        //if ($id = $this->_getParam('id', false)) {
        //stat
        
        //TODO: Here's how I think we can easily do show creation:
        // 	- More or less copy what SchedulerControler::addShowAction() does. (copy the contents of that function)
      
        $js = $this->_getParam('data', []);
        $data = array();
        
        //need to convert from serialized jQuery array.
        foreach ($js as $j) {
        	$data[$j["name"]] = $j["value"];
        }
        
        $service_show = new Application_Service_ShowService(null, $data);
        
        // TODO: move this to js
        $data['add_show_hosts']     = $this->_getParam('hosts');
        $data['add_show_day_check'] = $this->_getParam('days');
        
        if ($data['add_show_day_check'] == "") {
        	$data['add_show_day_check'] = null;
        }

        //FIXME: This line doesn't work...
        $scheduleController = new ScheduleController();
        $showFormService = new Application_Service_ShowFormService(null, $data);
        
        $forms = $scheduleController->createShowFormAction();
        
        $showFormService->view->addNewShow = true;
        
        if ($service_showForm->validateShowForms($forms, $data)) {
        	$service_show->addUpdateShow($data);
        
        	//send new show forms to the user
        	//$showFormService->createShowFormAction(true);
        	//$this->view->newForm = $this->view->render('schedule/add-show-form.phtml');
        
        	Logging::debug("Show creation succeeded");
        } else {
        	//$this->view->form = $this->view->render('schedule/add-show-form.phtml');
        	Logging::debug("Show creation failed");
        }
        

    
    }
    
    public function putAction()
    {
        if (!$this->verifyApiKey()) {
            return;
        }
        $id = $this->getId();
        if (!$id) {
            return;
        }
        
        $show = Airtime\CcShowQuery::create()->findPk($id);
        if ($show)
        {
            $show->importFrom('JSON', $this->getRequest()->getRawBody());
            $show->save();
            $this->getResponse()
                ->appendBody("From putAction() updating the requested show");
        } else {
            $this->showNotFoundResponse();
        }
    }
    
    public function deleteAction()
    {
        if (!$this->verifyApiKey()) {
            return;
        }
        $id = $this->getId();
        if (!$id) {
            return;
        }
        $show = Airtime\CcShowQuery::create()->$query->findPk($id);
        if ($show) {
            $show->delete();
        } else {
            $this->showNotFoundResponse();
        }
    }

    private function getId()
    {
        if (!$id = $this->_getParam('id', false)) {
            $resp = $this->getResponse();
            $resp->setHttpResponseCode(400);
            $resp->appendBody("ERROR: No show ID specified."); 
            return false;
        } 
        return $id;
    }

    private function verifyAPIKey()
    {
        //The API key is passed in via HTTP "basic authentication":
        //  http://en.wikipedia.org/wiki/Basic_access_authentication

        //TODO: Fetch the user's API key from the database to check against 
        $unencodedStoredApiKey = "foobar"; 
        $encodedStoredApiKey = base64_encode($unencodedStoredApiKey . ":");

        //Decode the API key that was passed to us in the HTTP request.
        $authHeader = $this->getRequest()->getHeader("Authorization");
        $encodedRequestApiKey = substr($authHeader, strlen("Basic "));

        if ($encodedRequestApiKey === $encodedStoredApiKey)
        {
            return true;
        }
        else
        {
            $resp = $this->getResponse();
            $resp->setHttpResponseCode(401);
            $resp->appendBody("ERROR: Incorrect API key."); 
            return false;
        }
    }

    private function showNotFoundResponse()
    {
        $resp = $this->getResponse();
        $resp->setHttpResponseCode(404);
        $resp->appendBody("ERROR: Show not found."); 
    }
}
