<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_Page_Generator {
    public static function pages() {
        return array(
            'education' => array('title'=>'Education Center','content'=>'[algq_education_home]'),
            'education/courses' => array('title'=>'Course Library','content'=>'[algq_course_list]'),
            'education/sellers' => array('title'=>'Seller Education','content'=>'[algq_education_track type="seller"]'),
            'education/buyers' => array('title'=>'Buyer Education','content'=>'[algq_education_track type="buyer"]'),
            'education/lenders' => array('title'=>'Lender Education','content'=>'[algq_education_track type="lender"]'),
            'education/acquisition' => array('title'=>'Acquisition Training','content'=>'[algq_education_track type="acquisition"]'),
            'education/platform-training' => array('title'=>'Platform Training','content'=>'[algq_platform_training]'),
            'education/products' => array('title'=>'Digital Product Library','content'=>'[algq_product_library]'),
            'education/progress' => array('title'=>'My Learning Progress','content'=>'[algq_user_progress]'),
        );
    }
}
