<?php

namespace ITCDataMigration;

class Exporter{

    const ROWS_PER_QUERY = 50;
    const ITC_DM_UPLOAD_NAME = 'itc-data-migration';
    private $page;
    private $exportDir;

    public function __construct()
    {
        $this->page = 1;
        $this->exportDir = wp_upload_dir();
        $this->exportDir['basedir'] = $this->exportDir['basedir'] .'/'. self::ITC_DM_UPLOAD_NAME;
        $this->exportDir['baseurl'] = $this->exportDir['baseurl'] .'/'. self::ITC_DM_UPLOAD_NAME;

    }
    public function exportData($postTypes = []) {

        if (empty($postTypes)) {
            return $this->emptyDataResponse();
        }


        $this->deleteOldJsonFiles();

        $fileName = 'export-data-'.date('Y-m-d').'-'.time().'.json';

        $fileNameLocation = $this->exportDir['basedir'] . '/' . $fileName;



        $this->createNewJsonFile($fileNameLocation);

        $exportData = [
            'source_home_url' => home_url(),
            'posts' => []
        ];

        foreach ($postTypes as $postTypeName => $postIDs) {
            $qrParams = $this->buildQueryParams($postTypeName, $postIDs);
            $qrPosts = new \WP_Query($qrParams);
            while($qrPosts->have_posts()):

                foreach($qrPosts->posts as $singlePost):
                    $exportData['posts'][] = [
                        'post_data' => $singlePost,
                        'related_post_meta' => $this->getPostMetaFields($singlePost->ID),
                        'related_terms' => $this->getRelatedTerms($singlePost->ID)
                    ];
                endforeach;
                $this->appendDataToExistingJsonFile($fileNameLocation, $exportData);
                $this->page++;
                $qrParams = $this->buildQueryParams($postTypeName, $postIDs);
                $qrPosts = new \WP_Query($qrParams);
            endwhile;

        }

        if ( empty($exportData['posts'])) {
            return $this->emptyDataResponse();
        }

        // Force download.
        $jsonContent = json_encode($exportData);
        header('Content-length: ' . strlen($jsonContent));
        header('Content-disposition: attachment; filename=json_data.json');
        die($jsonContent);

    }

    private function emptyDataResponse(){
        return [
            'status' => 'error',
            'msg' => 'No data to export'
        ];
    }

    private function appendDataToExistingJsonFile($fileLocation, $exportData){


        // Open the JSON file in read mode
        $handle = fopen($fileLocation, 'r');
        // $handle = Items::fromFile($fileName);

        // Create a buffer to store the JSON data
        $buffer = '';

        // Read the JSON data line by line
        while (!feof($handle)) {
            $buffer .= fgets($handle, 1024);
        }

        // Close the JSON file
        fclose($handle);

        // Decode the JSON data into an array
        $currentData = json_decode($buffer, true);

        // Add the new array to the JSON data

        $currentData = array_merge($currentData, $exportData);

        // Encode the JSON data back into a string
        $currentData = json_encode($currentData, JSON_PRETTY_PRINT);

        // Open the JSON file in write mode
        $handle = fopen($fileLocation, 'w');

        // Write the JSON data to the file
        fwrite($handle, $currentData);

        // Close the JSON file
        fclose($handle);
    }

    private function buildQueryParams($postType, $postIDs){
        $qrParams = [
            'post_type' => $postType,
            'posts_per_page' => self::ROWS_PER_QUERY,
            'offset' => $this->page > 1 ? ($this->page - 1) * self::ROWS_PER_QUERY : 0
        ];

        if ( !in_array('all', $postIDs) ) {
            $postIDs = array_map('intval', $postIDs);
            $qrParams['post__in'] = $postIDs;
            $qrParams['ignore_sticky_posts'] = true;
        }

        return $qrParams;
    }



    private function getRelatedTerms($postID){

        $allTaxs = get_taxonomies([
            'public'   => true,
            '_builtin' => false
        ]);

        //Append default taxonomies
        $allTaxs[] = 'category';
        $allTaxs[] = 'post_tag';

        $relatedTerms = [];

        if ( empty($allTaxs)) {
           return $relatedTerms;
        }

        foreach( $allTaxs as $taxName) {
            $terms = get_the_terms($postID, $taxName);
            if (is_wp_error($terms) || $terms === false ) {
                continue;
            }
            foreach($terms as $term){
                $relatedTerms[] = [
                    'term_item' => (array) $term,
                    'term_meta' => $this->getTermMetaFields($term->term_id)
                ];
            }
        }
        return $relatedTerms;

    }

    private function getPostMetaFields($postID) {
        $metaFields = get_post_meta($postID);
        return $this->formatACFFields($metaFields);
    }

    private function getTermMetaFields($termID){
        $metaFields = get_term_meta($termID);
        return $this->formatACFFields($metaFields);
    }

    private function formatACFFields($metaFields){
        $formatedMetaFields = [];

        if( empty($metaFields) ) {
            return $formatedMetaFields;
        }
        foreach ($metaFields as $metaKey => $metaValue) {

            $acfFieldType = $this->getACFFieldType($metaFields, $metaKey);

            $metaValue = $metaValue[0];
            $metaValue = $this->formatACFFieldValue($metaValue, $acfFieldType);

            //Check wordpress thumbnail id
            if ($metaKey == '_thumbnail_id') {
                $metaValue = wp_get_attachment_url($metaValue);
            }

            $formatedMetaFields[] = [
                'meta_key' => $metaKey,
                'meta_value' => $metaValue,
                'acf_type' => $acfFieldType,
            ];
        }
        return $formatedMetaFields;
    }

    private function formatACFFieldValue($metaValue, $acfFieldType){

        switch ($acfFieldType) {
            case 'image':
            case 'file':
                $metaValue = !empty($metaValue) ? wp_get_attachment_url($metaValue) : $metaValue;
                break;
            case 'gallery':
                $galleryImgIDs = maybe_unserialize($metaValue);
                $galleryImgUrls = [];
                if(!empty($galleryImgIDs)) {
                    foreach($galleryImgIDs as $attachmentID) {
                        $galleryImgUrls[] = wp_get_attachment_url($attachmentID);
                    }
                    $metaValue = json_encode($galleryImgUrls);
                }
                break;

            default:
                $metaValue = maybe_unserialize($metaValue);
        }
        return $metaValue;
    }




    private function getACFFieldType($availableMetaFields, $metaKey) {
        $acfKey = '_'.$metaKey;
        $acfFieldID = isset($availableMetaFields[$acfKey]) ? $availableMetaFields[$acfKey][0] : null;
        $acfType = null;

        if (is_null($acfFieldID)) {
            return $acfType;
        }
        //Match first position
        if( !empty($availableMetaFields) &&
            isset($availableMetaFields[$acfKey]) &&
            strpos($acfFieldID,'field_') === 0 ) {
                $acfType = get_field_object($acfFieldID)['type'];
        }
        return $acfType;

    }

    private function createNewJsonFile($fileName){

        wp_mkdir_p($this->exportDir['basedir']);
        $file = fopen($fileName, 'w');

        // Write the JSON data to the file.
        $data = [];

        fwrite($file, json_encode($data));

        // Close the file.
        fclose($file);
    }

    private function deleteOldJsonFiles(){
        $files = glob($this->exportDir['basedir'] . '/*');
        if (!empty($files)) {
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }
}