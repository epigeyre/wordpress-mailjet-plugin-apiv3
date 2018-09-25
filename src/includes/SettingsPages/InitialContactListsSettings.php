<?php

namespace MailjetPlugin\Includes\SettingsPages;

use MailjetPlugin\Includes\MailjetApi;
use MailjetPlugin\Includes\MailjetMail;
use MailjetPlugin\Admin\Partials\MailjetAdminDisplay;
use MailjetPlugin\Includes\MailjetSettings;
use MailjetPlugin\Includes\Mailjeti18n;

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 * @package    Mailjet
 * @subpackage Mailjet/includes
 * @author     Your Name <email@example.com>
 */
class InitialContactListsSettings
{
    public function mailjet_section_initial_contact_lists_cb($args)
    {
        ?>
        <h4 class="section_inner_title"><?php echo __('Configure your lists.', 'mailjet'); ?> </h4>
        <p class="top_descrption_helper" id="<?php echo esc_attr( $args['id'] ); ?>">
            <?php echo __('Here are the contact lists we have detected on your Mailjet account. You can add your Wordpress subscribers to one of them, or use them to collect new email addresses.', 'mailjet' ); ?>
        </p>
        <?php
    }


    public function mailjet_initial_contact_lists_cb($args)
    {
        // get the value of the setting we've registered with register_setting()
        $allWpUsers = get_users(array('fields' => array('ID', 'user_email')));
        $wpUsersCount = count($allWpUsers);
        $mailjetContactLists = MailjetApi::getMailjetContactLists();
        $mailjetContactLists = !empty($mailjetContactLists) ? $mailjetContactLists : array();
        $mailjetSyncActivated = get_option('activate_mailjet_sync');
        $mailjetInitialSyncActivated = get_option('activate_mailjet_initial_sync');
        $mailjetSyncList = get_option('mailjet_sync_list');

        // output the field
        ?>

        <h4 class="section_inner_title_slave"> <?php echo __('Your Mailjet contact lists', 'mailjet' ); ?> </h4>

        <div class="availableContactListsContainerParent" id="availableContactListsContainerParent">
        <div class="availableContactListsContainer">
            <?php // Display available contact lists and containing contacts
            foreach ($mailjetContactLists as $mailjetContactList) {
                if ($mailjetContactList["IsDeleted"] == true) {
                    continue;
                }
                ?>
                <div class="availableContactListsRow">
                    <div class="availableContactListsNameCell"><?=$mailjetContactList['Name'] ?></div>
                    <div class="availableContactListsCountCell"><?=$mailjetContactList['SubscriberCount'] ?> <?php echo  __('contacts', 'mailjet'); ?></div>
                </div>
                <?php
            }
            ?>
        </div>
        </div>

        <div class="create_contact_list_popup pop" id="create_contact_list_popup">
            <p><label for="create_list_name"><b><?php echo __('Name your list (max. 50 characters)', 'mailjet' ); ?></b></label>
                <input type="text" size="30" name="create_list_name" id="create_list_name" />
            </p>
            <input type="submit" value="<?=__('Save', 'mailjet')?>" name="create_contact_list_btn" class="MailjetSubmit nextBtn" id="create_contact_list_btn"/>
            <input name="nextBtn" class="nextBtn closeCreateList" type="button" id="nextBtn" value="<?=__('Cancel', 'mailjet')?>">
            <br style="clear: left;"/>
        </div>

        <img width="16" id="createContactListImg" src=" <?php echo plugin_dir_url(dirname(dirname(__FILE__))) . '/admin/images/create_contact_list.svg'; ?>" alt="<?php echo __('Create a new list', 'mailjet'); ?>" />
        <a id="create_contact_list" href="#"><?php echo __('Create a new list', 'mailjet' ); ?></a>
        <br /><br />

        <fieldset class="initialContactListsFieldset">
            <h4 class="section_inner_title"><?php echo __('Synchronize your Wordpress users', 'mailjet' ); ?></h4>
            <p><?php echo __('If you wish, you can add your Wordpress website users (readers, authors, administrators, …) to a contact list.', 'mailjet' ); ?></p>
            <legend class="screen-reader-text"><span><?php echo  __('Automatically add Wordpress subscribers to a specific list', 'mailjet'); ?></span></legend>
            <label for="activate_mailjet_sync">
                <input name="activate_mailjet_sync" type="checkbox" id="activate_mailjet_sync" value="1" <?=($mailjetSyncActivated == 1 ? ' checked="checked"' : '') ?> >
                <?php echo __('Automatically add all my future Wordpress subscribers to a specific contact list', 'mailjet'); ?></label>
            <br /><br />

            <div class="mailjet_sync_options_div">
                <select name="mailjet_sync_list" id="mailjet_sync_list" type="select" style="background-color: #F2F2F2; height: 36px; width: 312px;">
                    <?php
                    foreach ($mailjetContactLists as $mailjetContactList) {
                        if ($mailjetContactList["IsDeleted"] == true) {
                            continue;
                        }
                        ?>
                        <option value="<?= $mailjetContactList['ID'] ?>" <?= ($mailjetSyncList == $mailjetContactList['ID'] ? 'selected="selected"' : '') ?> > <?= $mailjetContactList['Name'] ?>
                            (<?= $mailjetContactList['SubscriberCount'] ?>)
                        </option>
                        <?php
                    } ?>
                </select>
                <br /><br />

                <label for="activate_mailjet_initial_sync">
                    <input name="activate_mailjet_initial_sync" type="checkbox" id="activate_mailjet_initial_sync" value="1" <?=($mailjetInitialSyncActivated == 1 ? ' checked="checked"' : '') ?> >
                    <?php echo sprintf(__('Also, add existing <b>%s Wordpress users</b> (initial synchronization)', 'mailjet'), $wpUsersCount); ?></label>
                <br /><br />
            </div>
        </fieldset>

        <input name="settings_step" type="hidden" id="settings_step" value="initial_contact_lists_settings_step">

        <?php
    }



    /**
     * top level menu:
     * callback functions
     */
    public function mailjet_initial_contact_lists_page_html()
    {
        $fromPage = !empty($_REQUEST['from']) ? $_REQUEST['from'] : null;

        // register a new section in the "mailjet" page
        add_settings_section(
            'mailjet_initial_contact_lists_settings',
            null,
            array($this, 'mailjet_section_initial_contact_lists_cb'),
            'mailjet_initial_contact_lists_page'
        );

        // register a new field in the "mailjet_section_developers" section, inside the "mailjet" page
        add_settings_field(
            'mailjet_enable_sending', // as of WP 4.6 this value is used only internally
            // use $args' label_for to populate the id inside the callback
            __( 'Enable sending emails through Mailjet', 'mailjet' ),
            array($this, 'mailjet_initial_contact_lists_cb'),
            'mailjet_initial_contact_lists_page',
            'mailjet_initial_contact_lists_settings',
            [
                'label_for' => 'mailjet_initial_contact_lists',
                'class' => 'mailjet_row',
                'mailjet_custom_data' => 'custom',
            ]
        );


        // check user capabilities
        if (!current_user_can('manage_options')) {
            \MailjetPlugin\Includes\MailjetLogger::error('[ Mailjet ] [ ' . __METHOD__ . ' ] [ Line #' . __LINE__ . ' ] [ Current user don\'t have \`manage_options\` permission ]');
            return;
        }

        // add error/update messages

        // check if the user have submitted the settings
        // wordpress will add the "settings-updated" $_GET parameter to the url
        if (isset($_GET['settings-updated'])) {

            $executionError = false;
            $applyAndContinueBtnClicked = false;

            // Initial sync WP users to Mailjet - when the 'create_contact_list_btn' button is not the one that submits the form
            if (empty(get_option('create_contact_list_btn')) && !empty(get_option('activate_mailjet_initial_sync')) && intval(get_option('mailjet_sync_list')) > 0) {
                $syncResponse = SubscriptionOptionsSettings::syncAllWpUsers();
                if (false === $syncResponse) {
                    $executionError = true;
                    update_option('contacts_list_ok', 0);
                    add_settings_error('mailjet_messages', 'mailjet_message', __('The settings could not be saved. Please try again or in case the problem persists contact Mailjet support.', 'mailjet'), 'error');
                }
            }

            // Create new Contact List
            if (!empty(get_option('create_contact_list_btn'))) {
                if (!empty(get_option('create_list_name'))) {
                    $createListResponse = MailjetApi::createMailjetContactList(get_option('create_list_name'));

                    if ($createListResponse->success()) {
                        add_settings_error('mailjet_messages', 'mailjet_message',
                            __('Congratulations! You have just created a new contact list!', 'mailjet'), 'updated');
                    } else {
                        $executionError = true;
                        update_option('contacts_list_ok', 0);

                        if (isset($createListResponse->getBody()['ErrorMessage']) && stristr($createListResponse->getBody()['ErrorMessage'], 'already exists')) {
                            add_settings_error('mailjet_messages', 'mailjet_message', sprintf(__('A contact list with name <b>%s</b> already exists', 'mailjet'), get_option('create_list_name')), 'error');
                        } else {
                            $executionError = true;
                            update_option('contacts_list_ok', 0);

                            add_settings_error('mailjet_messages', 'mailjet_message', __('The settings could not be saved. Please try again or in case the problem persists contact Mailjet support.', 'mailjet'), 'error');
                        }
                    }
                } else { // New list name empty
                    $executionError = true;
                    add_settings_error('mailjet_messages', 'mailjet_message', __('Please enter a valid contact list name', 'mailjet'), 'error');
                }
            } else {
                $applyAndContinueBtnClicked = true;
            }

            if (false === $executionError) {
                update_option('contacts_list_ok', 1);

                // add settings saved message with the class of "updated"
                add_settings_error('mailjet_messages', 'mailjet_message', __('Settings Saved', 'mailjet'), 'updated');

                if (!($fromPage == 'plugins') || (!empty(get_option('contacts_list_ok')) && '1' == get_option('contacts_list_ok'))) {
                    MailjetSettings::redirectJs(admin_url('/admin.php?page=mailjet_allsetup_page'));
                }
            }
        }
        if (!($fromPage == 'plugins') && (!empty(get_option('contacts_list_ok')) && '1' == get_option('contacts_list_ok'))) {
            MailjetSettings::redirectJs(admin_url('/admin.php?page=mailjet_dashboard_page'));
        }

        // show error/update messages
        settings_errors('mailjet_messages');

        ?>


        <div id="initialSettingsHead"><img src="<?php echo plugin_dir_url(dirname(dirname(__FILE__))) . '/admin/images/LogoMJ_White_RVB.svg'; ?>" alt="Mailjet Logo" /></div>
        <div class="mainContainer">

            <div>
                <p class="page_top_title"><?php echo __('Welcome to the Mailjet plugin for Wordpress', 'mailjet'); ?> </p>
                <p class="page_top_subtitle">
                    <?php echo __('Mailjet is an email service provider. With this plugin, easily send newsletters to your website users, directly from Wordpress.', 'mailjet'); ?>
                </p>
            </div>

            <div id="initialContactListsForm">
                <p class="section_title"><?php echo esc_html(get_admin_page_title()); ?></p>
                <form action="options.php" method="post">
                    <?php
                    // output security fields for the registered setting "mailjet"
                    settings_fields('mailjet_initial_contact_lists_page');
                    // output setting sections and their fields
                    // (sections are registered for "mailjet", each field is registered to a specific section)
                    do_settings_sections('mailjet_initial_contact_lists_page');
                    // output save settings button
                    if (MailjetApi::isValidAPICredentials()) {
                        submit_button('Apply and continue', 'MailjetSubmit', 'submit', false, array('id' => 'initialContactListsSubmit'));
                    } else {
                        update_option('settings_step', 'initial_step')
                        ?>
                        <input name="nextBtn" class="nextBtn" type="button" id="nextBtn" onclick="location.href = 'admin.php?page=mailjet_settings_page'" value="<?=__('Back', 'mailjet')?>">
                    <?php
                    } ?>

                    <input name="nextBtn" class="nextBtn" type="button" id="nextBtn" onclick="location.href = 'admin.php?page=mailjet_allsetup_page'" value="<?=(true !== $applyAndContinueBtnClicked) ? __('Skip this step', 'mailjet') : __('Next', 'mailjet')?>">

                    <br />
                </form>
            </div>

        </div>

        <div class="bottom_links">
            <div class="needHelpDiv">
                <img src=" <?php echo plugin_dir_url(dirname(dirname(__FILE__))) . '/admin/images/need_help.png'; ?>" alt="<?php echo __('Connect your Mailjet account', 'mailjet'); ?>" />
                <?php echo __('Need help getting started?', 'mailjet' ); ?>
            </div>
            <?php echo '<a target="_blank" href="' . Mailjeti18n::getMailjetUserGuideLinkByLocale() . '">' . __('Read our user guide', 'mailjet') . '</a>'; ?>
            <?php echo '<a target="_blank" href="' . Mailjeti18n::getMailjetSupportLinkByLocale() . '">' . __('Contact our support team', 'mailjet') . '</a>'; ?>
        </div>

        <?php

    }



}
