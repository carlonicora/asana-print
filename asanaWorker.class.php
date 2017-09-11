<?php
require_once 'template.class.php';
require_once 'asanaPages.class.php';

class asanaWorker {

    private $template;
    private $clientId;
    private $clientSecret;
    private $clientToken;
    private $refreshToken;
    private $page;
    private $asana;

    private $asanaWorkspace;
    private $asanaUser;

    public function render(){
        $this->analyseCallVariables();

        $this->initialiseEnv();

        session_start();

        $this->initialiseSessionVariables();

        $this->initialiseAsana();

        if (isset($this->clientToken)) {


            if (isset($this->asanaUser)){
                $this->page = asanaPages::PRINTABLE;
            } else {
                if (isset($this->asanaWorkspace)){
                    $this->page = asanaPages::AJAX_USERS;
                } else {
                    $this->page = asanaPages::INDEX;
                }
            }
        } else {
            if (isset($_GET['state'])){
                $this->receiveCallBack();
            } else {
                $this->page = asanaPages::LOGIN;
            }
        }

        $this->{$this->page}();

        $this->addTemplatesVariables();

        return($this->template->render('templates/'.$this->page.'.php'));
    }

    private function addTemplatesVariables(){
        $this->template->baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://'.$_SERVER['HTTP_HOST'].'/';
    }

    private function initialiseEnv(){
        $dotEnv = new Dotenv\Dotenv(__DIR__);
        $dotEnv->load();

        $this->clientId = getenv('ASANA_CLIENT_ID');
        $this->clientSecret = getenv('ASANA_CLIENT_SECRET');

        $this->template = new template();
    }

    private function initialiseSessionVariables(){
        if (isset($_COOKIE['clientToken'])){
            $this->clientToken = $_COOKIE['clientToken'];
        }

        if (isset($_COOKIE['refreshToken'])){
            $this->refreshToken = $_COOKIE['refreshToken'];
        }
    }

    private function initialiseAsana(){
        $this->setAsana();

        if (isset($this->refreshToken) && !isset($this->clientToken)) {
            $this->clientToken = $this->asana->dispatcher->refreshAccessToken();
            $this->saveToken();
            $this->setAsana();
        }
    }

    private function setAsana(){
        if (!isset($this->refreshToken)) {
            $this->asana = Asana\Client::oauth(array(
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://'.$_SERVER['HTTP_HOST']
            ));
        } else {
            $this->asana = Asana\Client::oauth(array(
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://'.$_SERVER['HTTP_HOST'],
                'token' => $this->clientToken,
                'refresh_token' => $this->refreshToken
            ));
        }
    }

    private function saveToken(){
        setcookie('clientToken', $this->clientToken, time() + 30, "/");
    }

    private function analyseCallVariables(){

        $uri = strtok($_SERVER["REQUEST_URI"],'?');

        if (!(isset($uri) && strlen($uri)==1 && $uri='/')){
            list($this->asanaWorkspace, $this->asanaUser) = array_pad(explode('/', substr($uri, 1), 2), 2, null);
        }
    }
    private function receiveCallBack(){
        if ($_SESSION['asanaState'] == $_GET['state']) {
            $this->clientToken = $this->asana->dispatcher->fetchToken($_GET['code']);

            $this->saveToken();

            $this->refreshToken = $this->asana->dispatcher->refreshToken;//$this->asana->dispatcher->refreshAccessToken();
            setcookie('refreshToken', $this->refreshToken, 0, "/");

            header('Location: /');
        } else {
            $this->template->failedLogin = true;
            $this->page = asanaPages::LOGIN;
        }
    }

    private function requireDefaultPage(){
        $this->template->content = $this->template->render('templates/'.$this->page.'.php');
        $this->page = asanaPages::DEFAULT_PAGE;
    }

    private function ajax_users(){
        $users = array();

        $asanaUsers = $this->asana->users->findByWorkspace($this->asanaWorkspace);

        foreach ($asanaUsers as $asanaUser) {
            $user = new stdClass();
            $user->name = $asanaUser->name ;
            $user->id = $asanaUser->id;

            $users[] = $user;
        }

        $this->template->workspaceId = $this->asanaWorkspace;
        $this->template->users = $users;
    }

    private function index(){
        $workspaces = array();

        $asanaWorkspaces = $this->asana->workspaces->findAll();

        foreach ($asanaWorkspaces as $asanaWorkspace) {
            $workspace = new stdClass();
            $workspace->name = $asanaWorkspace->name ;
            $workspace->id = $asanaWorkspace->id;

            $workspaces[] = $workspace;
        }

        $this->template->workspaces = $workspaces;

        $this->requireDefaultPage();
    }

    private function login(){
        $state = null;

        if (isset($_SESSION['asanaState'])) {
            $state = $_SESSION['asanaState'];
        }
        $url = $this->asana->dispatcher->authorizationUrl($state);

        if (!isset($this->template->failedLogin)){
            $this->template->failedLogin = false;
        }

        $this->template->url = $url;

        $_SESSION['asanaState'] = $state;

        $this->requireDefaultPage();
    }
    private function printable(){
        $user = $this->asana->users->findById($this->asanaUser);

        $asanaTasks = $this->asana->tasks->findAll(
            array(
                'assignee' => $this->asanaUser,
                'workspace' => $this->asanaWorkspace,
                'completed_since' => 'now'
            ),
            array('fields'=>array('name', 'assignee_status'))
        );

        $today = array();
        $upcoming = array();
        $new = array();


        $this->template->username = $user->name;

        if (isset($asanaTasks)) {
            foreach ($asanaTasks as $asanaTask) {
                $task = new stdClass();
                $task->name = $asanaTask->name;

                if ($asanaTask->assignee_status == 'today'){
                    $today[] = $task;
                } else if ($asanaTask->assignee_status == 'upcoming') {
                    $upcoming[] = $task;
                } else if ($asanaTask->assignee_status == 'inbox') {
                    $new[] = $task;
                }
            }
        }

        $this->template->today = $today;
        $this->template->upcoming = $upcoming;
        $this->template->new = $new;
    }
}