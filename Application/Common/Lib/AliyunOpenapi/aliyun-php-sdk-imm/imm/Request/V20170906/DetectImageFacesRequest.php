<?php

namespace imm\Request\V20170906;

/**
 * @deprecated Please use https://github.com/aliyun/openapi-sdk-php
 *
 * Request of DetectImageFaces
 *
 * @method string getImageUri()
 * @method string getProject()
 * @method string getRealUid()
 */
class DetectImageFacesRequest extends \RpcAcsRequest
{

    /**
     * @var string
     */
    protected $method = 'POST';

    /**
     * Class constructor.
     */
    public function __construct()
    {
        parent::__construct(
            'imm',
            '2017-09-06',
            'DetectImageFaces',
            'imm'
        );
    }

    /**
     * @param string $imageUri
     *
     * @return $this
     */
    public function setImageUri($imageUri)
    {
        $this->requestParameters['ImageUri'] = $imageUri;
        $this->queryParameters['ImageUri'] = $imageUri;

        return $this;
    }

    /**
     * @param string $project
     *
     * @return $this
     */
    public function setProject($project)
    {
        $this->requestParameters['Project'] = $project;
        $this->queryParameters['Project'] = $project;

        return $this;
    }

    /**
     * @param string $realUid
     *
     * @return $this
     */
    public function setRealUid($realUid)
    {
        $this->requestParameters['RealUid'] = $realUid;
        $this->queryParameters['RealUid'] = $realUid;

        return $this;
    }
}
