<?php

namespace Ponticlaro\Bebop\Media\Integrations;

use Ponticlaro\Bebop\Media\Config;
use Ponticlaro\Bebop\Media\Utils;

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

        // Make file uploads name unique, pre-submission
        add_action('gform_pre_submission', function() {
          add_filter('sanitize_file_name', [$this, '__makeFilenameUnique'], 10);
        });

        // Make file uploads name unique, after-submission
        add_action('gform_after_submission', function() {
          remove_filter('sanitize_file_name', [$this, '__makeFilenameUnique']);
        });
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
        $remote_base_url = Utils::getMediaBaseUrl();

        foreach ($form['fields'] as $field) {

            if ( $field->type == 'fileupload' &&
                 isset($entry[$field->id]) &&
                 $entry[$field->id] &&
                 $urls = static::getUrlsFromStringArray($entry[$field->id]) ) {

                 // Replace plain text array
                 if (count($urls) > 1) {

                   foreach ($urls as $index => $url) {
                     $urls[$index] = str_replace($local_base_url, $remote_base_url, $url);
                   }

                   $entry[$field->id] = '["'. implode('","', $urls) .'"]';
                 }

                 // Replace single URL string
                 else {
                   $entry[$field->id] = str_replace($local_base_url, $remote_base_url, $urls[0]);
                 }
            }
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
        $remote_base_url = Utils::getMediaBaseUrl();

        foreach ($form['fields'] as $field) {

            if ( $field->type == 'fileupload' &&
                 isset($entry[$field->id]) &&
                 $entry[$field->id] &&
                 $urls = static::getUrlsFromStringArray($entry[$field->id]) ) {

                foreach ($urls as $url) {
                  do_action('po_bebop_media.push_file_to_remote', str_replace($remote_base_url, '', $url));
                }
            }
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

        if ( $urls = static::getUrlsFromStringArray($entry[$field->id])) {

          $config          = Config::getInstance();
          $local_base_url  = $config->get('local.base_url');
          $remote_base_url = Utils::getMediaBaseUrl();

          $value = '<ul>';

          foreach ($urls as $url) {
            $remote_url = str_replace($local_base_url, $remote_base_url, $url);
            $value .= '<li><a href="'. $remote_url .'">'. basename($remote_url) .'</a></li>';
          }

          $value .= '</ul>';
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
            if ( $field->type == 'fileupload' &&
                 isset($entry[$field->id]) &&
                 $entry[$field->id] &&
                 $urls = static::getUrlsFromStringArray($entry[$field->id]) ) {

                foreach ($urls as $url) {
                  do_action('po_bebop_media.delete_file_from_remote', str_replace($local_base_url, '', $url));
                }
            }
        }
    }

    /**
     * Used to make sure files uploaded via gravity forms have unique names
     *
     * @param  string $filename Original file name
     * @return string           Filename with timestamp
     */
    function __makeFilenameUnique($filename)
    {
        return date('U') .'-'. $filename;
    }

    /**
     * Gets upload URLs from plain text array
     *
     * @param  string string Array in plain text
     * @return array         List of URLs
     */
    protected static function getUrlsFromStringArray($string)
    {
      // Remove backslashes
      $string = str_replace('\/', '/', $string);

      // Remove square brackets on both ends
      $string = rtrim(ltrim($string, '['), ']');

      // Remove double quotes on both ends
      $string = trim($string, '"');

      // Split string into URLs
      $urls = explode('","', $string);

      return $urls && $urls[0] != '' ? $urls : [];
    }
}
