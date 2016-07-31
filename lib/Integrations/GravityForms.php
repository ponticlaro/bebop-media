<?php 

namespace Ponticlaro\Bebop\Media\Integrations;

use Ponticlaro\Bebop\Media\Config;

class GravityForms {

    /**
     * Instantiates GravityForms integration
     * 
     */
    public function __construct() 
    {
        add_action('gform_entry_post_save', [$this, '__modifyEntryArray'], 10, 2);
        add_action('gform_after_submission', [$this, '__uploadFilesToRemote'], 10, 2);
        add_action('gform_entry_field_value', [$this, '__modifyLeadDetails'], 10, 4);
        add_action('gform_delete_lead', [$this, '__deleteFilesFromRemote'], 10, 1);
    }

    /**
     * Modify submission entry array after being saved.
     * The goal is to provide the modified entry array to notifications and confirmation emails
     *
     * @link   https://www.gravityhelp.com/documentation/article/gform_entry_post_save/ Gravity Forms hook documentation
     * @param  array $entry Gravity Forms entry
     * @param  array $form  Gravity Forms form
     * @return array        Modified entry
     */
    public function __modifyEntryArray($entry, $form)
    {
        $config          = Config::getInstance();
        $local_base_url  = $config->get('local.base_url');
        $remote_base_url = $config->getMediaBaseUrl();

        foreach ($form['fields'] as $field) {

            if ($field->type == 'fileupload' && isset($entry[$field->id]) && $entry[$field->id])
                $entry[$field->id] = str_replace($local_base_url, $remote_base_url, $entry[$field->id]);
        }

        return $entry;
    }

    /**
     * Uploads all fileupload fields to remote storage
     *
     * @link   https://www.gravityhelp.com/documentation/article/gform_after_submission/ Gravity Forms hook documentation
     * @param  array $entry Gravity Forms entry
     * @param  array $form  Gravity Forms form
     * @return void   
     */
    public function __uploadFilesToRemote($entry, $form)
    {
        $remote_base_url = Config::getInstance()->getMediaBaseUrl();;

        foreach ($form['fields'] as $field) {

            if ($field->type == 'fileupload' && isset($entry[$field->id]) && $entry[$field->id])
                do_action('po_bebop_media.push_file_to_remote', str_replace($remote_base_url, '', $entry[$field->id]));
        }
    }

    /**
     * Modifies lead details for uploaded files
     * The goal is to replace local URLs with remote ones within WordPress administration
     *
     * @link   https://www.gravityhelp.com/documentation/article/gform_entry_field_value/ Gravity Forms hook documentation
     * @param  array $value  Gravity Forms form
     * @param  array $field  Gravity Forms form
     * @param  array $entry  Gravity Forms form
     * @param  array $form   Gravity Forms entry
     * @return void
     */
    public function __modifyLeadDetails($value, $field, $entry, $form)
    {
        if ($field->type != 'fileupload')
            return $value;

        $config          = Config::getInstance();
        $local_base_url  = $config->get('local.base_url');
        $remote_base_url = $config->getMediaBaseUrl();
        $file_url        = isset($entry[$field->id]) && $entry[$field->id] ? $entry[$field->id] : null;
        
        if ($file_url) {

            $file_url = str_replace($local_base_url, $remote_base_url, $file_url);
            $value    = preg_replace("/<a(.*)href='([^']*)'(.*)>/", '<a$1href="'. $file_url .'"$3>', $value);
        }

        return $value;
    }

    /**
     * Deletes remote files
     *
     * @link   https://www.gravityhelp.com/documentation/article/gform_delete_lead/ Gravity Forms hook documentation
     * @param  string $entry_id Entry ID
     * @return void
     */
    public function __deleteFilesFromRemote($entry_id)
    {
        // Get entry and form objects
        $entry = \GFAPI::get_entry($entry_id);
        $form  = \GFAPI::get_form($entry['form_id']);

        // Get local base URL
        $local_base_url = Config::getInstance()->get('local.base_url');

        foreach ($form['fields'] as $field) {

            // Delete remote file
            if ($field->type == 'fileupload' && isset($entry[$field->id]) && $entry[$field->id])
                do_action('po_bebop_media.delete_file_from_remote', str_replace($local_base_url, '', $entry[$field->id]));
        }
    }
}