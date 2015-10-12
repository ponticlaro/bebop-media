<?php

namespace Ponticlaro\Bebop\Media;

class MediaEncoder {

    const LOG_META_KEY = 'aws_encoder_log';

    const SOURCE_META_KEY = 'aws_encoder_source';

    const JOB_ID_META_KEY = 'aws_encoder_job_id';

    const URLS_META_KEY = 'aws_encoder_urls';

    protected $post_id;

    protected $encoder;

    protected $source;

    protected $target;

    protected $job_id;

    protected $urls;

    protected $error;

    public function __construct(\Ponticlaro\Encoding\Encoder $encoder)
    {
        $this->encoder = $encoder;
    }

    public function encodeAndSave($post_id, $source, $target)
    {
        if (!is_integer($post_id))
            throw new UnexpectedValueException("Both source and target must be strings");

        if (!is_string($source) || !is_string($target))
            throw new UnexpectedValueException("Both source and target must be strings");

        $this->post_id = $post_id;
        $this->source  = $source;
        $this->target  = $target;

        try {
            $this->job_id = $this->encoder->encode($this->source, $this->target);
            $this->urls   = $this->encoder->getTargetUrls($this->source, $this->target, $this->job_id);

        } catch (Exception $e) {

            $this->error = $e->getMessage();
        }

        $this->__saveMeta();
    }

    protected function __saveMeta()
    {
        if (is_null($this->error)) {
        
            $config = Config::getInstance();

            // Delete job log
            delete_post_meta($this->post_id, static::LOG_META_KEY);

            // Set URLs array
            $data = [
                'mp4'  => [],
                'flv'  => [],
                'webm' => []
            ];

            $cdn_enabled        = $config->get('cdn.enabled') && $config->get('cdn.domain') ? true : false;
            $replaced_string    = $cdn_enabled ? 's3://'. $config->get('storage.s3.bucket') . ($config->get('storage.s3.prefix') ? '/'. $config->get('storage.s3.prefix') : '') : 's3://';
            $replacement_string = $cdn_enabled ? $config->get('url_scheme') .'://'. $config->get('cdn.domain') . ($config->get('cdn.prefix') ? '/'. $config->get('cdn.prefix') : '') : 'https://s3.amazonaws.com/';

            // Collect MP4
            if(isset($this->urls['1080p']) && $this->urls['1080p']) {
                $data['mp4']['url']    = str_replace($replaced_string, $replacement_string, $this->urls['1080p']['video']);
                $data['mp4']['poster'] = str_replace($replaced_string, $replacement_string, $this->urls['1080p']['thumbnail']);
            }

            // Collect FLV
            if(isset($this->urls['flashVideo']) && $this->urls['flashVideo']) {
                $data['flv']['url']    = str_replace($replaced_string, $replacement_string, $this->urls['flashVideo']['video']);
                $data['flv']['poster'] = str_replace($replaced_string, $replacement_string, $this->urls['flashVideo']['thumbnail']);
            }

            // Collect WEBM
            if(isset($this->urls['1080p.webm']) && $this->urls['1080p.webm']) {
                $data['webm']['url']    = str_replace($replaced_string, $replacement_string, $this->urls['1080p.webm']['video']);
                $data['webm']['poster'] = str_replace($replaced_string, $replacement_string, $this->urls['1080p.webm']['thumbnail']);
            }
            
            // Save URLs
            update_post_meta($this->post_id, static::URLS_META_KEY, $data);
        }

        else {

            // Update encoder log and source
            update_post_meta($this->post_id, static::LOG_META_KEY, [
                'error'  => $this->error,
                'source' => sanitize_text_field($this->source),
                'target' => sanitize_text_field($this->target)
            ]);

            // Delete URLs
            delete_post_meta($this->post_id, static::URLS_META_KEY);
        }
    }
}