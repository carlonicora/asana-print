<?php
require_once 'template.class.php';
require_once 'asanaPages.class.php';

class asanaWorker {

    private $template;
    private $clientId;
    private $clientSecret;
    private $clientToken;
    private $page;
    private $asana;

    private $asanaWorkspace;
    private $asanaUser;

    public function render(){
        $this->initialiseEnv();

        session_start();

        $this->initialiseSessionVariables();

        $this->initialiseAsana();

        if (isset($this->clientToken)) {
            $this->analyseCallVariables();

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
        if (isset($_SESSION['clientToken'])){
            $this->clientToken = $_SESSION['clientToken'];
        } else {
            if (isset($_COOKIE['clientToken'])){
                $this->clientToken = $_SESSION['token'] = $_COOKIE['clientToken'];
            }
        }

        if (isset($this->clientToken)){
            $this->initialiseAsana();
        }
    }

    private function initialiseAsana(){
        $this->asana = Asana\Client::oauth(array(
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://'.$_SERVER['HTTP_HOST'],
            'token' => $this->clientToken
        ));
    }

    private function analyseCallVariables(){
        if (!(isset($_SERVER['REQUEST_URI']) && strlen($_SERVER['REQUEST_URI'])==1 && $_SERVER['REQUEST_URI']='/')){
            $uri = substr($_SERVER['REQUEST_URI'], 1);
            list($this->asanaWorkspace, $this->asanaUser) = array_pad(explode('/', $uri, 2), 2, null);
        }
    }

    private function receiveCallBack(){
        if ($_SESSION['state'] == $_GET['state']) {
            $temporaryToken = $this->asana->dispatcher->fetchToken($_GET['code']);
            $this->clientToken = $_SESSION['clientToken'] = $this->asana->dispatcher->refreshAccessToken();

            setcookie('clientToken', $this->clientToken, time() + (86400 * 30), "/");
            header('Location: /');
            exit;
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
        $url = $this->asana->dispatcher->authorizationUrl($state);

        if (!isset($this->template->failedLogin)){
            $this->template->failedLogin = false;
        }

        $this->template->url = $url;

        $_SESSION['state'] = $state;

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