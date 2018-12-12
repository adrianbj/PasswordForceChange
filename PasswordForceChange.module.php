<?php

/**
 * ProcessWire Password Force Change
 * by Adrian Jones
 *
 * Force users to change password the first time they log in
 *
 * ProcessWire 3.x
 * Copyright (C) 2011 by Ryan Cramer
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 *
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

class PasswordForceChange extends WireData implements Module, ConfigurableModule {

    /**
     * Basic information about module
     */
    public static function getModuleInfo() {
        return array(
            'title' => 'Force Password Change',
            'summary' => 'Force users to change password.',
            'author' => 'Adrian Jones',
            'href' => 'http://modules.processwire.com/modules/password-force-change/',
            'version' => '1.0.3',
            'autoload' => true,
            'singular' => true,
            'icon' => 'key',
            'requires' => 'ProcessWire>=2.5.14',
        );
    }

    /**
     * Data as used by the get/set functions
     *
     */
    protected $data = array();


   /**
     * Default configuration for module
     *
     */
    static public function getDefaultData() {
            return array(
                "automaticForceChange" => "",
                "autoloadFrontend" => null,
                "frontendLoginUrl" => ""
            );
    }

    /**
     * Populate the default config data
     *
     */
    public function __construct() {
       foreach(self::getDefaultData() as $key => $value) {
               $this->$key = $value;
       }
    }

    public function init() {
    }

    public function ready() {
        //exit now if front-end and autoloadFrontend not checked
        if($this->wire('page')->template != 'admin' && !$this->data['autoloadFrontend']) return;

        $this->wire()->addHookBefore('Pages::saveReady', $this, 'saveUserChecks');
        $this->wire()->addHookAfter('PageRender::renderPage', $this, 'profileRedirect');
        $this->wire()->addHookAfter('Password::setPass', $this, 'passwordChanged');
        $this->wire()->addHookBefore('InputfieldPassword::render', $this, 'adjustPasswordField');
        $this->wire()->addHookAfter('InputfieldPassword::processInput', $this, 'passwordProcessed'); //just for checking if password fields are empty
    }

    protected function saveUserChecks(HookEvent $event) {

        $page = $event->arguments[0];

        if(!in_array($page->template->id, $this->wire('config')->userTemplateIDs)) return; //return now if not a user template

        if($page->isNew()) {
            if($this->data['automaticForceChange']) $page->force_passwd_change = 1;
        }
        elseif($page->force_passwd_change) { //if force_passwd_change not checked we don't need to worry about whether they have profile-edit permission
            $process = $this->wire('process');
            if($process instanceof WirePageEditor) {
                $newuser = $process->getPage();
                if(!$newuser->hasPermission("profile-edit")) {
                    $newuser->force_passwd_change = ''; //uncheck since they can't edit their profile
                    $this->wire()->error($this->_("This user does not have profile-edit permission so they won't be able to change their password, so the \"Force password change on next login\" setting was unchecked. Please give them this permission and try again."));
                }
            }
        }
        else {
            return;
        }
    }

    protected function profileRedirect() {
        if($this->wire('user')->force_passwd_change && $this->wire('user')->isLoggedin()) {
            $this->wire()->error($this->_("You must change your password and it must not match your last password."));
            $f = $this->wire('fields')->get("pass");
            $f->collapsed = Inputfield::collapsedNo;
            $f->notes = "You must change your password now.";

            //if not already on the profile page, redirect to it
            if($this->wire('page')->template != 'admin' && $this->data['autoloadFrontend'] && $this->data['frontendLoginUrl'] != '') {
                $redirectUrl = $this->data['frontendLoginUrl'];
            }
            elseif(!$this->wire('config')->ajax) {
                $redirectUrl = $this->wire('config')->urls->admin."profile/";
            }

            if(isset($redirectUrl) && $this->wire('input')->url(true) != $redirectUrl) $this->wire('session')->redirect($redirectUrl);
        }
        else {
            return;
        }
    }

    protected function adjustPasswordField(HookEvent $event) {
        if($this->wire('user')->force_passwd_change && $this->wire('user')->isLoggedin()) {
            $process = $this->wire('process');
            if($process instanceof WirePageEditor) {
                $inputfield = $event->object;
                $inputfield->notes = __('You must change your password and it must not match your last password.');
                $inputfield->collapsed = Inputfield::collapsedNo;
            }
        }
        else {
            return;
        }
    }

    protected function passwordChanged() {
        if($this->wire('user')->isChanged("pass")) {
            $this->wire()->message($this->_("Thank you for changing your password."));
            $this->wire('user')->of(false);
            $this->wire('user')->force_passwd_change = ''; //uncheck once password has been changed
            $this->wire('user')->save();
        }
        else {
            $this->profileRedirect();
        }

    }

    protected function passwordProcessed(HookEvent $event) {
        if($event->object->value == '') {
            $this->profileRedirect();
        }

    }


    /**
     * Return an InputfieldsWrapper of Inputfields used to configure the class
     *
     * @param array $data Array of config values indexed by field name
     * @return InputfieldsWrapper
     *
     */
    public function getModuleConfigInputfields(array $data) {

        $this->wire()->addHookBefore('Modules::saveModuleConfigData', $this, 'onConfigSave');

        $data = array_merge(self::getDefaultData(), $data);

        $wrapper = new InputfieldWrapper();

        $f = $this->wire('modules')->get("InputfieldCheckbox");
        $f->attr('name', 'autoloadFrontend');
        $f->label = __('Load on Front-end');
        $f->description = __('If checked, this module will be loaded on the front-end, as well as in the back-end admin.');
        $f->columnWidth = 50;
        $f->attr('checked', $data['autoloadFrontend'] ? 'checked' : '' );
        $wrapper->add($f);

        $f = $this->wire('modules')->get("InputfieldURL");
        $f->attr('name', 'frontendLoginUrl');
        $f->showIf = "autoloadFrontend=1";
        $f->label = __('Frontend Login URL');
        $f->description = __('If you have a front-end login form and you are loading this module on the front-end, enter the URL to your profile editing page where they can change their password. You can enter a root relative or absolute URL.');
        $f->notes = __('This will not affect users in the backend - they will still be redirected to the main profile editing page.');
        $f->columnWidth = 50;
        if($data['frontendLoginUrl']) $f->attr('value', $data['frontendLoginUrl']);
        $wrapper->add($f);

        $f = $this->wire('modules')->get("InputfieldCheckbox");
        $f->attr('name', 'automaticForceChange');
        $f->label = __('Automatic Force Change');
        $f->description = __('If checked, the "Force Password Change" option will be automatically checked for each new user when they are created.');
        $f->attr('checked', $data['automaticForceChange'] ? 'checked' : '' );
        $wrapper->add($f);

        $fieldset = $this->wire('modules')->get("InputfieldFieldset");
        $fieldset->attr('id', 'setAllUsers');
        $fieldset->label = "Set All Users";
        $fieldset->collapsed = Inputfield::collapsedYes;

        $f = $this->wire('modules')->get("InputfieldRadios");
        $f->attr('name', 'bulkAction');
        $f->label = __('Bulk Action');
        $f->description = __('The "Check" option will immediately check the "Force Password Change" option for all existing users. You can use the "Clear" option to revert this and uncheck it for all users.');
        $f->notes = __("Use with extreme caution! This will force all users (except you) to change their password on their next login or admin page view\nThis may take a long time if you have a lot of users.");
        $f->addOption('none', 'No Action');
        $f->addOption('1', 'Check');
        $f->addOption('', 'Clear');
        $f->value = 'none';
        $fieldset->add($f);

        $f = $this->wire('modules')->get("InputfieldCheckboxes");
        $f->attr('name', 'allowedRoles');
        $f->required = 1;
        $f->requiredIf = "bulkAction!=none";
        $f->label = __('User roles to check or clear the Force Password Change option.');
        $f->description = __('The "Check" or "Clear" option will only apply to these selected roles.');
        $f->notes = __('This list is limited to only roles that have the "profile-edit" permission, otherwise the user wouldn\'t be able to change their password');

        // populate with all available roles
        foreach($this->wire('roles') as $roleoption) {
            if($roleoption->hasPermission("profile-edit")) $f->addOption($roleoption->name); //limit to only roles that have permission to edit their profile
        }

        $fieldset->add($f);

        $wrapper->add($fieldset);

        return $wrapper;
    }

    protected function onConfigSave(HookEvent $event) {
        $arguments = $event->arguments;
        if($arguments[0] != 'PasswordForceChange') return;
        $data = $arguments[1];
        if($data['bulkAction']!='none') {
            ini_set('max_execution_time', 300);
            foreach($this->wire('users') as $u) {
                if($this->wire('user') != $u && $u->roles->has("name=".implode("|",$data['allowedRoles']))) {
                    if($u->hasPermission("profile-edit") || $data['bulkAction'] == '') { //shouldn't be necessary because selectables roles are already limited, but just in case permissions are changed between loading of config page and running the batch setting.
                        $u->of(false);
                        $u->force_passwd_change = $data['bulkAction']; // 1 for check, blank for clear (uncheck)
                        $u->save();
                    }
                }
            }
        }
    }

    public function ___install() {

        //Create force_passwd_change field
        if(!$this->wire('fields')->force_passwd_change) {
            $f = new Field();
            $f->type = "FieldtypeCheckbox";
            $f->name = "force_passwd_change";
            $f->label = "Force password change on next login";
            $f->description = "This is used by the Force Password Change module. You can check this at any time to force the user to change their password on next login.";
            $f->notes = "This will be automatically unchecked when the user changes their password.";
            $f->collapsed = Inputfield::collapsedBlank;
            $f->save();

            foreach($this->wire('config')->userTemplateIDs as $userTemplateId) {
                $userTemplate = $this->wire('templates')->get($userTemplateId);
                $userTemplate->fields->add($f);
                $userTemplate->fields->save();
            }

        }

    }

    public function ___uninstall() {

        //remove force_passwd_change field
        if($this->wire('fields')->force_passwd_change) {

            $f = $this->wire('fields')->force_passwd_change;

            foreach($this->wire('config')->userTemplateIDs as $userTemplateId) {
                $userTemplate = $this->wire('templates')->get($userTemplateId);
                $userTemplate->fields->remove($f);
                $userTemplate->fields->save();
            }

            $this->wire('fields')->delete($f);

        }

    }

}

