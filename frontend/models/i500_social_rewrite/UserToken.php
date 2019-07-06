<?php
/**
 * 用户token model
 *
 * PHP Version 5
 *
 * @category  MODEL
 * @package   Social
 * @author    liuyanwei <liuyanwei@i500m.com>
 * @time      2017-04-13
 * @copyright 辽宁i500科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      liuyanwei@i500m.com
 */
namespace frontend\models\i500_social_rewrite;

use Yii;

class UserToken extends SocialRewriteBase
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'i500_user_token';
    }
}
