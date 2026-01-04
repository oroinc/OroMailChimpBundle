<?php

namespace Oro\Bundle\MailChimpBundle\Model\MergeVar;

/**
 * Merge variable data holder interface.
 *
 * @link http://apidocs.mailchimp.com/api/2.0/lists/merge-vars.php
 */
interface MergeVarInterface
{
    /**#@+
     * @const string Field type of MergeVar
     */
    public const FIELD_TYPE_EMAIL = 'email';
    public const FIELD_TYPE_PHONE = 'phone';
    /**#@-*/

    /**#@+
     * @const string Tags of MergeVar
     */
    public const TAG_EMAIL = 'EMAIL';
    public const TAG_FIRST_NAME = 'FNAME';
    public const TAG_LAST_NAME = 'LNAME';
    /**#@-*/

    /**#@+
     * @const string Name of properties of MergeVar
     */
    public const PROPERTY_NAME = 'name';
    public const PROPERTY_REQUIRED = 'req';
    public const PROPERTY_FIELD_TYPE = 'field_type';
    public const PROPERTY_TAG = 'tag';
    /**#@-*/

    /**
     * @return bool
     */
    public function isFirstName();

    /**
     * @return bool
     */
    public function isLastName();

    /**
     * @return bool
     */
    public function isEmail();

    /**
     * @return bool
     */
    public function isPhone();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getFieldType();

    /**
     * @return string
     */
    public function getTag();
}
