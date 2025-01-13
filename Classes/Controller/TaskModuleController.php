<?php

declare(strict_types=1);

namespace TYPO3\CMS\Taskcenter\Controller;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Taskcenter\TaskInterface;

/**
 * This class provides a task center for BE users
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class TaskModuleController
{
    /**
     * Loaded with the global array $MCONF which holds some module configuration from the conf.php file of backend modules.
     *
     * @see init()
     * @var array
     */
    protected $MCONF = [];

    /**
     * The integer value of the GET/POST var, 'id'. Used for submodules to the 'Web' module (page id)
     *
     * @see init()
     * @var int
     */
    protected $id;

    /**
     * The module menu items array. Each key represents a key for which values can range between the items in the array of that key.
     *
     * @see init()
     * @var array
     */
    protected $MOD_MENU = [
        'function' => [],
    ];

    /**
     * Current settings for the keys of the MOD_MENU array
     * Public since task objects use this.
     *
     * @see $MOD_MENU
     * @var array
     */
    public $MOD_SETTINGS = [];

    /**
     * Module TSconfig based on PAGE TSconfig / USER TSconfig
     * Public since task objects use this.
     *
     * @see menuConfig()
     * @var array
     */
    public $modTSconfig;

    /**
     * Generally used for accumulating the output content of backend modules
     *
     * @var string
     */
    protected $content = '';

    /**
     * @var array
     */
    protected $pageinfo;

    /**
     * ModuleTemplate Container
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'user_task';

    protected ModuleInterface $currentModule;

    /**
     * Initializes the Module
     */
    public function __construct(
        protected ModuleTemplateFactory $moduleTemplateFactory,
        protected UriBuilder $uriBuilder,
        protected PageRenderer $pageRenderer,
        private readonly BackendViewFactory $backendViewFactory
    ) {
        $this->getLanguageService()->includeLLFile('EXT:taskcenter/Resources/Private/Language/locallang_task.xlf');
        $this->MCONF = [
            'name' => $this->moduleName,
        ];
        // Name might be set from outside
        if (!$this->MCONF['name']) {
            $this->MCONF = $GLOBALS['MCONF'];
        }
        $this->id = (int)($GLOBALS['TYPO3_REQUEST']->getParsedBody()['id'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['id'] ?? null);
        $this->menuConfig();
    }

    /**
     * Adds items to the ->MOD_MENU array. Used for the function menu selector.
     */
    protected function menuConfig()
    {
        $this->MOD_MENU = ['mode' => [], 'function' => null];
        $languageService = $this->getLanguageService();
        $this->MOD_MENU['mode']['information'] = $languageService->sL('LLL:EXT:taskcenter/Resources/Private/Language/locallang.xlf:task_overview');
        $this->MOD_MENU['mode']['tasks'] = $languageService->sL('LLL:EXT:taskcenter/Resources/Private/Language/locallang.xlf:task_tasks');
        // Copied from parent::menuConfig, because parent is hardcoded to menu.function,
        // however menu.function is already used for the individual tasks. Therefore we use menu.mode here.
        // Page/be_user TSconfig settings and blinding of menu-items
        $this->modTSconfig['properties'] = BackendUtility::getPagesTSconfig($this->id)['mod.'][$this->moduleName . '.'] ?? [];
        $this->MOD_MENU['mode'] = $this->mergeExternalItems($this->MCONF['name'], 'mode', $this->MOD_MENU['mode']);
        $blindActions = $this->modTSconfig['properties']['menu.']['mode.'] ?? [];
        foreach ($blindActions as $key => $value) {
            if (!$value && array_key_exists($key, $this->MOD_MENU['mode'])) {
                unset($this->MOD_MENU['mode'][$key]);
            }
        }
        // Page / user TSconfig settings and blinding of menu-items
        // Now overwrite the stuff again for unknown reasons
        $this->modTSconfig['properties'] = BackendUtility::getPagesTSconfig($this->id)['mod.'][$this->MCONF['name'] . '.'] ?? [];
        $this->MOD_MENU['function'] = $this->mergeExternalItems($this->MCONF['name'], 'function', $this->MOD_MENU['function']);
        $blindActions = $this->modTSconfig['properties']['menu.']['function.'] ?? [];
        foreach ($blindActions as $key => $value) {
            if (!$value && array_key_exists($key, $this->MOD_MENU['function'])) {
                unset($this->MOD_MENU['function'][$key]);
            }
        }
        $this->MOD_SETTINGS = BackendUtility::getModuleData(
            $this->MOD_MENU,
            $GLOBALS['TYPO3_REQUEST']->getParsedBody()['SET'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['SET'] ?? [],
            $this->MCONF['name']
        );
    }

    /**
     * Generates the menu based on $this->MOD_MENU
     *
     * @throws \InvalidArgumentException
     */
    protected function generateMenu()
    {
        $menu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('WebFuncJumpMenu');
        foreach ($this->MOD_MENU['mode'] as $controller => $title) {
            $item = $menu
                ->makeMenuItem()
                ->setHref(
                    (string)$this->uriBuilder->buildUriFromRoute(
                        $this->moduleName,
                        [
                            'id' => $this->id,
                            'SET' => [
                                'mode' => $controller,
                            ],
                        ]
                    )
                )
                ->setTitle($title);
            if ($controller === $this->MOD_SETTINGS['mode']) {
                $item->setActive(true);
            }
            $menu->addMenuItem($item);
        }
        $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    /**
     * Injects the request object for the current request or subrequest
     * Simply calls main() and writes the content to the response
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $this->currentModule = $request->getAttribute('module');
        $mode = (string)$this->MOD_SETTINGS['mode'];
        $this->getButtons();
        $this->generateMenu();
        $this->moduleTemplate->setTitle($this->getLanguageService()->sL('LLL:EXT:taskcenter/Resources/Private/Language/locallang.xlf:taskcenter'));
        if ($mode === 'information') {
            return $this->renderInformationContent();
        }
        return $this->renderModuleContent();

    }

    /**
     * Generates the module content by calling the selected task
     */
    protected function renderModuleContent(): ResponseInterface
    {
        $languageService = $this->getLanguageService();
        $chosenTask = (string)$this->MOD_SETTINGS['function'];
        // Render the taskcenter task as default
        if (empty($chosenTask) || $chosenTask === 'index') {
            $chosenTask = 'taskcenter.tasks';
        }
        // Render the task
        $actionContent = '';
        $flashMessage = null;
        [$extKey, $taskClass] = explode('.', $chosenTask, 2);
        if (class_exists($taskClass)) {
            $taskInstance = GeneralUtility::makeInstance($taskClass, $this, $this->pageRenderer);
            if ($taskInstance instanceof TaskInterface) {
                // Check if the task is restricted to admins only
                if ($this->checkAccess($extKey, $taskClass)) {
                    $actionContent .= $taskInstance->getTask();
                } else {
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        $languageService->sL('LLL:EXT:taskcenter/Resources/Private/Language/locallang_task.xlf:error-access'),
                        $languageService->sL('LLL:EXT:taskcenter/Resources/Private/Language/locallang_task.xlf:error_header'),
                        ContextualFeedbackSeverity::ERROR
                    );
                }
            } else {
                // Error if the task is not an instance of \TYPO3\CMS\Taskcenter\TaskInterface
                $flashMessage = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    sprintf($languageService->sL('LLL:EXT:taskcenter/Resources/Private/Language/locallang_task.xlf:error_no-instance'), $taskClass, TaskInterface::class),
                    $languageService->sL('LLL:EXT:taskcenter/Resources/Private/Language/locallang_task.xlf:error_header'),
                    ContextualFeedbackSeverity::ERROR
                );
            }
        } else {
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $languageService->sL('LLL:EXT:taskcenter/Resources/Private/Language/locallang_mod.xlf:mlang_labels_tabdescr'),
                $languageService->sL('LLL:EXT:taskcenter/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'),
                ContextualFeedbackSeverity::INFO
            );
        }

        if ($flashMessage) {
            /** @var FlashMessageService $flashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            /** @var FlashMessageQueue $defaultFlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }

        $assigns = [];
        $assigns['reports'] = $this->indexAction();
        $assigns['taskClass'] = strtolower(str_replace('\\', '-', htmlspecialchars($extKey . '-' . $taskClass)));
        $assigns['actionContent'] = $actionContent;
        $this->moduleTemplate->assignMultiple($assigns);
        return $this->moduleTemplate->renderResponse('ModuleContent');
    }

    /**
     * Generates the information content
     */
    protected function renderInformationContent(): ResponseInterface
    {
        $assigns = [];
        $assigns['LLPrefix'] = 'LLL:EXT:taskcenter/Resources/Private/Language/locallang.xlf:';
        $assigns['LLPrefixMod'] = 'LLL:EXT:taskcenter/Resources/Private/Language/locallang_mod.xlf:';
        $assigns['LLPrefixTask'] = 'LLL:EXT:taskcenter/Resources/Private/Language/locallang_task.xlf:';
        $assigns['admin'] = $this->getBackendUser()->isAdmin();
        $this->moduleTemplate->assignMultiple($assigns);
        return $this->moduleTemplate->renderResponse('InformationContent');

    }

    /**
     * Render the headline of a task including a title and an optional description.
     * Public since task objects use this.
     *
     * @param string $title Title
     * @param string $description Description
     * @return string formatted title and description
     */
    public function description($title, $description = '')
    {
        $descriptionView = $this->backendViewFactory->create($GLOBALS['TYPO3_REQUEST'], ['friendsoftypo3/taskcenter']);
        $descriptionView->assign('title', $title);
        $descriptionView->assign('description', $description);
        return $descriptionView->render('Task/Description');
    }

    /**
     * Render a list of items as a nicely formatted definition list including a link, icon, title and description.
     * The keys of a single item are:
     * - title:             Title of the item
     * - link:              Link to the task
     * - icon:              Path to the icon or Icon as HTML if it begins with <img
     * - description:       Description of the task, using htmlspecialchars()
     * - descriptionHtml:   Description allowing HTML tags which will override the description
     * Public since task objects use this.
     *
     * @param array $items List of items to be displayed in the definition list.
     * @param bool $mainMenu Set it to TRUE to render the main menu
     * @return string Formatted definition list
     */
    public function renderListMenu($items, $mainMenu = false)
    {
        $assigns = [];
        $assigns['mainMenu'] = $mainMenu;

        // Change the sorting of items to the user's one
        if ($mainMenu) {
            $userSorting = unserialize($this->getBackendUser()->uc['taskcenter']['sorting'] ?? '');
            if (is_array($userSorting)) {
                $newSorting = [];
                foreach ($userSorting as $item) {
                    if (isset($items[$item])) {
                        $newSorting[] = $items[$item];
                        unset($items[$item]);
                    }
                }
                $items = $newSorting + $items;
            }
        }
        if (is_array($items) && !empty($items)) {
            foreach ($items as $itemKey => &$item) {
                $id = $this->getUniqueKey($item['uid']);
                $contentId = strtolower(str_replace('\\', '-', $id));
                $item['uniqueKey'] = $id;
                $item['contentId'] = $contentId;
                // Collapsed & expanded menu items
                if (isset($this->getBackendUser()->uc['taskcenter']['states'][$id]) && $this->getBackendUser()->uc['taskcenter']['states'][$id]) {
                    $item['ariaExpanded'] = 'true';
                    $item['collapseIcon'] = 'actions-view-list-expand';
                    $item['collapsed'] = '';
                } else {
                    $item['ariaExpanded'] = 'false';
                    $item['collapseIcon'] = 'actions-view-list-collapse';
                    $item['collapsed'] = 'show';
                }
                // Active menu item
                $panelState = (string)$this->MOD_SETTINGS['function'] == $item['uid'] ? 'panel-primary' : 'panel-default';
                $item['panelState'] = $panelState;
            }
        }
        $assigns['items'] = $items;

        $view = $this->backendViewFactory->create($GLOBALS['TYPO3_REQUEST'], ['friendsoftypo3/taskcenter']);
        $view->assignMultiple($assigns);
        return $view->render('ListMenu');
    }

    /**
     * Shows an overview list of available reports.
     *
     * @return string List of available reports
     */
    protected function indexAction()
    {
        $languageService = $this->getLanguageService();
        $content = '';
        $tasks = [];
        $defaultIcon = 'module-taskcenter';
        // Render the tasks only if there are any available
        if (count($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['taskcenter'] ?? [])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['taskcenter'] as $extKey => $extensionReports) {
                foreach ($extensionReports as $taskClass => $task) {
                    if (!$this->checkAccess($extKey, $taskClass)) {
                        continue;
                    }
                    $link = (string)$this->uriBuilder->buildUriFromRoute('user_task') . '&SET[function]=' . $extKey . '.' . $taskClass;
                    $taskTitle = $languageService->sL($task['title']);
                    $taskDescriptionHtml = '';

                    if (class_exists($taskClass)) {
                        $taskInstance = GeneralUtility::makeInstance($taskClass, $this, $this->pageRenderer);
                        if ($taskInstance instanceof TaskInterface) {
                            $taskDescriptionHtml = $taskInstance->getOverview();
                        }
                    }
                    // Generate an array of all tasks
                    $uniqueKey = $this->getUniqueKey($extKey . '.' . $taskClass);
                    $tasks[$uniqueKey] = [
                        'title' => $taskTitle,
                        'descriptionHtml' => $taskDescriptionHtml,
                        'description' => $languageService->sL($task['description']),
                        'icon' => !empty($task['icon']) ? $task['icon'] : $defaultIcon,
                        'link' => $link,
                        'uid' => $extKey . '.' . $taskClass,
                    ];
                }
            }
            $content .= $this->renderListMenu($tasks, true);
        } else {
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $languageService->sL('LLL:EXT:taskcenter/Resources/Private/Language/locallang_task.xlf:no-tasks'),
                '',
                ContextualFeedbackSeverity::INFO
            );
            /** @var FlashMessageService $flashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            /** @var FlashMessageQueue $defaultFlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }
        return $content;
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise
     * perform operations.
     */
    protected function getButtons()
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier($this->currentModule->getIdentifier())
            ->setDisplayName($this->getLanguageService()->sL($this->currentModule->getTitle()));
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    /**
     * Check the access to a task. Considered are:
     * - Admins are always allowed
     * - Tasks can be restriced to admins only
     * - Tasks can be blinded for Users with TsConfig taskcenter.<extensionkey>.<taskName> = 0
     *
     * @param string $extKey Extension key
     * @param string $taskClass Name of the task
     * @return bool Access to the task allowed or not
     */
    protected function checkAccess($extKey, $taskClass): bool
    {
        $backendUser = $this->getBackendUser();
        // Admins are always allowed
        if ($backendUser->isAdmin()) {
            return true;
        }
        // Check if task is restricted to admins
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['taskcenter'][$extKey][$taskClass]['admin']) && (int)$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['taskcenter'][$extKey][$taskClass]['admin'] === 1) {
            return false;
        }
        // Check if task is blinded with TsConfig (taskcenter.<extkey>.<taskName>
        return (bool)($backendUser->getTSConfig()['taskcenter.'][$extKey . '.'][$taskClass] ?? true);
    }

    /**
     * Create a unique key from a string which can be used in JS for sorting
     * Therefore '_' are replaced
     *
     * @param string $string string which is used to generate the identifier
     * @return string Modified string
     */
    protected function getUniqueKey($string)
    {
        $search = ['.', '_'];
        $replace = ['-', ''];
        return str_replace($search, $replace, $string);
    }

    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Public since task objects use this.
     *
     * @return ModuleTemplate
     */
    public function getModuleTemplate(): ModuleTemplate
    {
        return $this->moduleTemplate;
    }

    /**
     * Merges menu items from global array $TBE_MODULES_EXT
     *
     * @param string $modName Module name for which to find value
     * @param string $menuKey Menu key, eg. 'function' for the function menu.
     * @param array $menuArr The part of a MOD_MENU array to work on.
     * @return array Modified array part.
     * @internal
     * @see \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(), menuConfig()
     */
    protected function mergeExternalItems($modName, $menuKey, $menuArr)
    {
        $mergeArray = $GLOBALS['TBE_MODULES_EXT'][$modName]['MOD_MENU'][$menuKey] ?? false;
        if (is_array($mergeArray)) {
            foreach ($mergeArray as $k => $v) {
                if (((string)$v['ws'] === '' || $this->getBackendUser()->workspace === 0 && GeneralUtility::inList($v['ws'], 'online')) || $this->getBackendUser()->workspace === -1 && GeneralUtility::inList($v['ws'], 'offline') || $this->getBackendUser()->workspace > 0 && GeneralUtility::inList($v['ws'], 'custom')) {
                    $menuArr[$k] = $this->getLanguageService()->sL($v['title']);
                }
            }
        }
        return $menuArr;
    }
}
