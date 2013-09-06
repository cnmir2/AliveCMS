<?php

class Bugtracker extends MX_Controller{
    
    private $css = array();
    private $moduleTitle = 'Bugtracker';
    private $modulePath = 'modules/bugtracker/';
    
    public function __construct(){
        //Call the constructor of MX_Controller
        parent::__construct();
        
        requirePermission('view');

        // Dummys
        if(false){
            $this->bug_model = new Bug_model();
            $this->project_model = new Project_Model();
            $this->template = new Template();
            $this->external_account_model = new External_account_model();
        }
        
        $this->load->model('bug_model');
        $this->load->model('project_model');
        $this->load->helper('string');

        // Realm DB
        $this->connection = $this->external_account_model->getConnection();
        
        // Breadcrumbs
        $this->template->addBreadcrumb('Server', site_url('server/index'));
        $this->template->addBreadcrumb('Bugtracker', site_url('bugtracker/index'));

        $this->template->setJsAction('bugtracker');

        $this->template->hideSidebar();
    }

    /**
     * Shows all Bug Projects
     */
    public function index(){
        requirePermission('view');

        $this->template->setTitle($this->moduleTitle);
        $this->template->setSectionTitle($this->moduleTitle);
        $projectList = $this->project_model->getProjects();
        $projectCount = count($projectList);

        $projectChoices = array();
        $baseProjects = array();
        $projectsByParent = array();


        foreach($projectList as $l0project){

            $l0key = $l0project['id'];

            $projectData = $this->project_model->getAllProjectData($l0key, $l0project);

            $l0project['counts'] = array(
                'open' => $projectData['counts'][BUGSTATE_OPEN]+$projectData['counts'][BUGSTATE_ACTIVE],
                'done' => $projectData['counts'][BUGSTATE_DONE],
                'all' => $projectData['counts']['all'],
                'percentage' => array(
                    'done' => $projectData['counts']['percentage'][BUGSTATE_DONE]
                )
            );

            // Icons
            if(!empty($l0project['icon'])){
                $iconPath = $l0project['icon'];
                if(substr_count($iconPath, 'patch') > 0){
                    $iconPath = 'images/icons/patch/'.$iconPath.'.jpg';
                }
                else{
                    $iconPath = 'images/icons/36/'.$iconPath.'.jpg';
                }

                $localPath = APPPATH.'themes/'.$this->template->theme.'/'.$iconPath;
                $webPath = base_url().$localPath;
                //debug('local', $localPath);

                $l0project['icon'] = file_exists($localPath)
                    ? $webPath
                    : base_url().'themes/'.$this->template->theme.'/'.'images/icons/36/ability_creature_cursed_02.grey.jpg?'.$iconPath;
            }

            if($l0project['parent'] != 0){
                $projectsByParent[$l0project['parent']][$l0project['id']] = $l0project;
            }
            else{
                $baseProjects[$l0project['id']] = $l0project;
            }
        }

        // Level 0 Projects
        foreach($baseProjects as $l0key => $l0project){

            //$projectChoices[$l0key] = $l0project['title'];
            //debug('Level 0', $l0project);
            /*$l0all = $l0project['counts']['all'];
            $l0done = $l0project['counts']['done'];
            $l0open = $l0project['counts']['open'];*/
            $l1projects = array();

            if(!empty($projectsByParent[$l0key])){

                // Level 1 Projects of this project
                $l1projects = $projectsByParent[$l0key];

                // Foreach level 1 Project of this Level 1 project
                foreach($l1projects as $l1key => $l1project){

                    //debug('L1',$l1project);

                    $l1all = $l1project['counts']['all'];
                    $l1done = $l1project['counts']['done'];
                    $l1open = $l1project['counts']['open'];
                    $l2projects = array();

                    if(!empty($projectsByParent[$l1key])){

                        // Level 2 Projects
                        $l2projects = $projectsByParent[$l1key];

                        foreach($l2projects as $l2key => $l2project){
                            // Add 'done' and 'all' counts to the Level-1
                            $l1done += $l2project['counts']['done'];
                            $l1all += $l2project['counts']['all'];
                            $l1open += $l2project['counts']['open'];
                        }

                        // Save L2 back to L1 stack
                        $l1projects[$l1key]['projects'] = $l2projects;
                    }

                    $l1projects[$l1key]['projects'] = $l2projects;
                    $l1projects[$l1key]['counts']['all'] = $l1all;
                    $l1projects[$l1key]['counts']['done'] = $l1done;
                    $l1projects[$l1key]['counts']['open'] = $l1open;
                    if($l1all > 0){
                        //debug('L1 '.$l1project['title'], '$l1done/$l1all');
                        $l1projects[$l1key]['counts']['percentage']['done'] = round($l1done/$l1all*100);
                    }

                    // Add 'done' and 'all' counts to the L0
                    /*$l0done += $l1projects[$l1key]['counts']['done'];
                    $l0open += $l1projects[$l1key]['counts']['open'];
                    $l0all += $l1projects[$l1key]['counts']['all'];*/


                }

                /*$baseProjects[$l0key]['counts']['done'] = $l0done;
                $baseProjects[$l0key]['counts']['all'] = $l0all;
                $baseProjects[$l0key]['counts']['open'] = $l0open;*/
                $all = $l0project['counts']['all'];
                $done = $l0project['counts']['done'];
                $baseProjects[$l0key]['counts']['percentage']['done'] = round($done/$all*100);


                // Save L1 back to L0 stack (Base)
                $baseProjects[$l0key]['projects'] = $l1projects;
            }

        }

        //debug('base', $baseProjects);


        // Prepare my data
        $templateData = array(
            'url' => $this->template->page_url,
            'projects' => $baseProjects,
            'projectCount' => $projectCount,
            'projectChoices' => $projectChoices,
        );

        // Load my view
        $out = $this->template->loadPage('project_list.tpl', $templateData);

        $this->template->view($out, $this->css);
    }

    /**
     * Show all Bugs of a project
     * @param $projectId
     */
    public function buglist($projectId){

        requirePermission('view');

        $project = $this->project_model->getProjectById($projectId, 'id,title,matpath');

        if(!$project){
            show_error("Das Bugtracker Projekt wurde nicht gefunden.");
            return;
        }
        $searchProjects = array($projectId);

        $this->template->setTitle($project['title']." - ".$this->moduleTitle);
        $this->template->setSectionTitle($this->moduleTitle.": ".$project['title']);

        $projects = array(
            $projectId => $project
        );

        $subProjects = $this->project_model->getSubProjects($projectId);

        if($subProjects){

            $subProjectIds = array();

            foreach($subProjects as $sub){
                $subProjectIds[] = $sub['id'];
                $projects[$sub['id']] = $sub;
            }

            $searchProjects = array_merge($searchProjects, $subProjectIds);
        }

        /*
         * Breadcrumbs via MatPath
         */
        $projectPath = explode('.',$project['matpath']);

        while(count($projectPath)){
            $currentBreadcrumb = array_pop($projectPath)*1;
            if($currentBreadcrumb == $projectId){
                $this->template->addBreadcrumb($project['title']);
            }
            else{
                $currentParent = $this->project_model->getProjectById($currentBreadcrumb, 'id,title');
                if($currentParent){
                    $this->template->addBreadcrumb($currentParent['title'], site_url('bugtracker/buglist/'.$currentParent['id']));
                }
            }
        }

        $this->template->enable_profiler(true);

        $bugRows = $this->bug_model->getBugsByProject($projectId, 'none');

        foreach($bugRows as $i => $row){
            $row['title'] = htmlentities($row['title'], ENT_QUOTES, 'UTF-8');

            $row['css'] = '';

            $row['changedSort'] = strftime('%Y-%m-%d', $row['changedTimestamp']);

            $row['type_string'] = $projects[$row['project']]['title'];

            $row['priorityClass'] = $this->bug_model->getPriorityCssClass($row['priority']);
            $row['priorityLabel'] = $this->bug_model->getPriorityLabel($row['priority']);

            switch($row['bug_state']){
                case BUGSTATE_DONE:
                    $row['css'] = 'done';
                    break;
                case BUGSTATE_ACTIVE:
                    $row['css'] = 'inprogress';
                    break;
                case BUGSTATE_REJECTED:
                    $row['css'] = 'disabled';
                    break;
                case BUGSTATE_OPEN:
                default:
                    $row['css'] = 'fresh';
                    $row['status'] = 0;
                    break;
            }

            $bugRows[$i] = $row;

        }

        $page_data = array(
            'module' => 'bugtracker',
            'bugRows' => $bugRows,
            'rowCount' => count($bugRows),
            'rowMax' => min($bugRows,50),
            'rowMin' => ($bugRows == 0) ? 0 : 1,
            'js_path' => $this->template->js_path,
            'image_path' => $this->template->image_path,
        );

        $out = $this->template->loadPage('list.tpl', $page_data);

        $this->template->view($out, $this->css);
    }

    /**
     * Show detail page for a bug
     * @param $bugId
     */
    public function bug($bugId){
        requirePermission('view');


        if(!is_numeric($bugId)){
            show_404('Dieser Link ist ungültig');
            return;
        }

        $bug = $this->bug_model->getBug($bugId);

        if($bug === false){
            show_error('Der gesuchte Bug wurde nicht gefunden.');
            return;
        }

        $this->template->setTitle('Bug #'.$bugId);
        $this->template->setSectionTitle('Bug #'.$bugId.' '.htmlentities($bug['title'], ENT_QUOTES, 'UTF-8'));

        $this->template->addBreadcrumb('Bug #'.$bugId, site_url('bugtracker/bug/'.$bugId));

        /*
         * Base Data
         */

        $class = $bug['class'];
        $title = htmlentities($bug['title'], ENT_QUOTES, 'UTF-8');
        $desc = $bug['desc'];
        $state = $bug['bug_state'];
        $complete = str_replace('%','',$bug['complete']);
        $complete .= '%';
        $by = $bug['by'];
        $date = $bug['date'];
        $date2 = $bug['date2'];
        $link = (substr_count($bug['link'], 'Hier den') > 0) ? '' : $bug['link'];
        $links = array();
        $createdDetail = '';
        $changedDetail = '';
        $accountComments = array();

        /**
         * Log of all actions
         * @type {Array}
         */
        $bugLog = array();

        /**
         * Similar Bugs
         * @type {Array}
         */
        $similarBugs = array();

        /*
         * Link
         */
        if(!empty($link)){
            if(preg_match('@http://(de|www|old).wowhead.com/\??([^=]+)=(\d+).*@i', $link, $matches)){
                $links = array();
                debug('link matches',$matches);
                $links[] = '<a href="http://de.wowhead.com/'.$matches[2].'='.$matches[3].'" target="_blank">WoWHead</a>';
                if( $matches[2] == 'zone' ){
                    $links[] = '<a href="http://portal.wow-alive.de/game/zone/'.$matches[3].'" target="_blank" data-zone="'.$matches[3].'">Alive</a>';
                }
                if( $matches[2] == 'item' ){
                    $links[] = '<a href="http://portal.wow-alive.de/item/'.$matches[3].'" target="_blank" data-item="'.$matches[3].'">Alive</a>';
                }
            }
        }

        // Find other bugs to the same link
        $search = str_replace('http://','', $link);
        $search = str_replace('de.wowhead.com/','',$search);
        $search = str_replace('www.wowhead.com/','',$search);
        $search = str_replace('old.wowhead.com/','',$search);

        $similarBugs = $this->bug_model->findSimilarBugs($search, $bugId);

        if(!empty($link)){
            foreach($similarBugs as $row){
                $otherBugs[] = '<a href="/bugtracker/bug/'.$row['id'].'/" target="_blank">'.htmlentities($row['title']).'</a>';
            }
        }

        // Find links in the description
        $desc = htmlentities($desc);
        $desc = makeWowheadLinks($desc);


        switch($state){
            case BUGSTATE_DONE:
                $cssState = 'color-q2'; break;
            case BUGSTATE_OPEN:
            case BUGSTATE_ACTIVE:
                $cssState = 'color-q1'; break;
            case BUGSTATE_REJECTED:
                $cssState = 'color-q0'; break;
        }

        if($bug['createdTimestamp'] > 0){
            $createdDetail = sec_to_dhms(time()-$bug['createdTimestamp'], true);
            if(!empty($createdDetail))
                $createdDetail = 'vor '.$createdDetail;
        }
        if($bug['changedTimestamp'] > 0){
            $changedDetail = sec_to_dhms(time()-$bug['changedTimestamp'], true);
            if(!empty($changedDetail))
                $changedDetail = 'vor '.$changedDetail;
        }

        if(!empty($bug['posterData'])){
            $posterData = json_decode($bug['posterData']);
            //debug('posterData',$posterData);

            $bugPoster = array(
                'details' => true,
                'name' => $posterData->name,
                'class' => $posterData->class,
                'url' => '',    // TODO Link zur Armory integrieren
            );
        }
        else{
            $bugPoster = array(
                'details' => false,
            );
        }

        $commentRows = $this->bug_model->getBugComments($bugId);

        $counter = 1;
        //$rowclass = 'row1';

        foreach($commentRows as $i => $row){
            $actionLog = array();

            //$rowclass = cycle($rowclass, array('row1', 'row2'));
            //$commentRows[$i]['css-row'] = $rowclass;

            $commentRows[$i]['id'] = $row['id'];
            $commentRows[$i]['n'] = $counter++;
            $commentRows[$i]['gm'] = false;
            $commentRows[$i]['avatar'] = '';
            $commentRows[$i]['action'] = '';
            $commentRows[$i]['lastEdit'] = '';
            $commentRows[$i]['name'] = htmlentities($row['name']);
            $commentRows[$i]['text'] = nl2br(makeWowheadLinks(htmlentities($row['text'])));
            $commentRows[$i]['date'] = ($row['timestamp'] > 60) ? 'vor '.sec_to_dhms(time()-$row['timestamp'],true):'';
            $commentRows[$i]['canEditThisComment'] = hasPermission('canEditComments');

            // State changes
            if(!empty($row['action'])){
                $actions = json_decode($row['action']);
                if(isset($actions->state)){
                    $actionLog[] = 'Status => '.$actions->state;
                }
            }
            // Content changes
            if(!empty($row['actions'])){
                $actions = json_decode($row['actions']);
                $lastEdit = '';
                foreach($actions as $action){
                    if($action->action == 'change'){
                        $name = ($action->gm) ? '[GM] '.$action->name : $action->name;
                        $lastEdit = '<span class="time">von '.$name.' bearbeitet vor <span data-tooltip="'.strftime('%d.%m.%Y',$action->ts).'">'.sec_to_dhms(time()-$action->ts,true).'</span></span>';
                    }
                }
                if(!empty($lastEdit)){
                    $actionLog[] = $lastEdit;
                }
            }
            $commentRows[$i]['action'] = implode('<br/>', $actionLog);

            if(!empty($row['posterData'])){
                $posterData = json_decode($row['posterData']);

                if(empty($posterData->realmId)){
                    $posterData->realmId = 1;
                }

                $commentRows[$i]['details'] = true;
                $commentRows[$i]['avatar'] = $this->realms->formatAvatarPath(array(
                    'class' => $posterData->class,
                    'race' => $posterData->race,
                    'gender' => $posterData->gender,
                    'level' => $posterData->level
                ));
                $commentRows[$i]['char_url'] = $this->realms->getArmoryUrl($posterData->name, $posterData->realmId);
                $commentRows[$i]['char_class'] = $posterData->class;
            }

            if(!empty($row['posterAccountId'])){

                $rank = $this->external_account_model->getRank($row['posterAccountId']);

                if($rank){
                    $commentRows[$i]['gm'] = true;
                }
            }

            $bugLog[$row['timestamp']] = $commentRows[$i];

        }

        // Combine Actions and Comments (later)
        $bugActionLog = array();

        if(!empty($bug['actions'])){
            $actions = json_decode($bug['actions']);

            foreach($actions as $action){
                $ts = $action->ts;
                $bugLog[$ts] = '<span class="time">'.sec_to_dhms(time()-$ts, true, 'vor ').'</span> '.$action->name.' => Bug Report bearbeitet';
            }

        }

        ksort($bugLog);

        /*
         * User Specific
         */
        $activeCharGuid = $this->user->getActiveCharacter();
        $activeRealmId = $this->user->getActiveRealmId();

        if($activeCharGuid > 0){
            $activeCharacter = $this->user->getActiveCharacterData();
            $activeCharacter['active'] = true;
            $activeCharacter['url'] = $this->realms->getArmoryUrl($activeCharacter['name'], $activeRealmId);
            $activeCharacter['avatar'] = $this->realms->formatAvatarPath($activeCharacter);
        }
        else{
            $activeCharacter = array(
                'active' => false,
            );
        }

        /*
         * Template Generation
         */
        $page_data = array(
            'module' => 'bugtracker',
            'canEditBugs' => hasPermission('canEditBugs'),
            'bugId' => $bugId,
            'bugStates' => $this->bug_model->getBugStates(),
            'typeString' => '',
            'title' => $title,
            'cssState' => $cssState,
            'state' => $state,
            'stateLabel' => $this->bug_model->getStateLabel($state),
            'class' => '',

            'createdDetail' => $createdDetail,
            'date' => $date,
            'date2' => $date2,
            'changedDetail' => $changedDetail,
            'complete' => $complete,
            'links' => $links,
            'bugPoster' => $bugPoster,
            'desc' => nl2br($desc),
            'similarBugs' => $similarBugs,
            'activeCharacter' => $activeCharacter,
            /*'state' => $asd,
            'state' => $asd,
            'state' => $asd,
            'state' => $asd,*/
        );

        $out = $this->template->loadPage('bug_detail.tpl', $page_data);

        $this->template->view($out, $this->css);
    }

    public function create(){

        requirePermission('canCreateBugs');

        // Helper
        $this->load->helper('form');

        /**
         * Post Form
         */
        $formData = array(
            'project' => '',
            'title' => '',
            'desc' => '',
            'priority' => BUGPRIORITY_MINOR,
            'links' => array(),
        );

        foreach($formData as $fieldName => $defaultValue){
            $postData = $this->input->post($fieldName);

            if($fieldName == 'priority' && !hasPermission("canPrioritize")){
                continue;
            }

            if(!empty($postData)){
                $formData[$fieldName] = $postData;
            }
        }
        //debug("formData", $formData);

        if(!empty($formData['project']) && !empty($formData['title']) && !empty($formData['desc'])){
            $newBugId = $this->bug_model->create($formData['project'], $formData['priority'], $formData['title'], $formData['desc'], $formData['links']);

            if($newBugId){
                // Show Detail Page of the newly created Bug
                $this->bug($newBugId);
                return;
            }
        }

        /**
         * Title & Breadcrumbs
         */
        $this->template->setTitle($this->moduleTitle);
        $this->template->setSectionTitle('Neuen Bug eintragen');
        $this->template->addBreadcrumb('Neuen Bug eintragen', site_url('bugtracker/create'));


        /**
         * Bug Categories
         */
        $projectTree = $this->project_model->getProjectTree();

        $baseProjects = array();
        $projectPaths = array();

        foreach($projectTree as $baseRow){
            $projectPaths[$baseRow['id']] = explode('.',$baseRow['matpath']);
            $children = array();

            foreach($baseRow['children'] as $child1){
                $projectPaths[$child1['id']] = explode('.', $child1['matpath']);
                $children[$child1['id']] = $child1['prefix'].$child1['title'];

                foreach($child1['children'] as $child2){
                    $projectPaths[$child2['id']] = explode('.', $child2['matpath']);
                    $children[$child2['id']] = $child2['prefix'].$child2['title'];

                    foreach($child2['children'] as $child3){
                        $projectPaths[$child3['id']] = explode('.', $child3['matpath']);
                        $children[$child3['id']] = $child3['prefix'].$child3['title'];
                    }
                }
            }

            $baseProjects[$baseRow['id']] = array(
                'title' => $baseRow['prefix'].$baseRow['title'],
                'children' => $children,
            );
        }

        $idTypes = array(
            'quest' => 'Quest',
            'npc' => 'NPC',
            'zone' => 'Dungeon/Raid/Zone',
        );

        $bugPriorities = $this->bug_model->getPrioritiesWithLabel();

        $page_data = array(
            'module' => 'bugtracker',
            'form_attributes' => array('class' => 'form-horizontal', 'id' => 'bugtrackerCreateForm'),
            'js_path' => $this->template->js_path,
            'image_path' => $this->template->image_path,
            'baseProjects' => $baseProjects,
            'idTypes' => $idTypes,
            'projectPaths' => $projectPaths,
            'bugLinks' => array(),
            'bugPriorities' => $bugPriorities,
            'post' => $formData,
        );
        
        $out = $this->template->loadPage('bug_create.tpl', $page_data);
        
        $this->template->view($out, $this->css);
    }
    
}
    