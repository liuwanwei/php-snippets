<?php

/**
 * 角色管理帮助类，需要依赖 yii2-admin 
 */

namespace common\helpers;

use Yii;
use mdm\admin\components\Configs;
use mdm\admin\models\Assignment;
use mdm\admin\models\searchs\AuthItem as AuthItemSearch;
use yii\rbac\Item;

class RoleHelper{

	/**
	 * 获取用户被赋予的所有角色列表
	 *
	 * @param integer $userId
	 * @return array
	 */
	public static function roleForUser(int $userId){
		$model = new Assignment($userId);
		$manager = Yii::$app->getAuthManager();
		$items = $model->getItems();

		$assigned = $items['assigned'];
		$roles = [];
		foreach ($assigned as $name => $value) {
			$item = $manager->getRole($name);
			if (!empty($item)) {
				$roles[] = $item;
			}
		}

		return $roles;
	}


	/**
	 * 检查一个用户是否具备给定的权限
	 *
	 * @param string $roleName	权限的名字
	 * @param integer $userId		要检查的用户，不传或传 0 会自动取当前用户
	 * @return void
	 */
	public static function checkRoleForUser(string $roleName, int $userId = 0){		
		if ($userId === 0) {
			if (Yii::$app->user->isGuest) {
				return false;
			}
			
			$userId = Yii::$app->user->identity->id;
		}

		$roles = RoleHelper::roleForUser($userId);
		foreach ($roles as $role) {
        if ($role->name == $roleName) {
					return true;
				}
		}

		return false;
	}

	/**
	 *
	 * 为了区分后台账户能够针对不同用户分配的角色级别，比如为幼儿园账户分配幼儿园级角色，
	 * 为集团内部管理账户分配集团级角色，所以定义了下面的标签。在权限管理后台创建角色时，
	 * 需要为对应角色设置下面的 data 字段，如“集团查看员”，data 字段设置为：
	 * {"kgroup":1}
	 *
	 */


	/**
	 *
	 * 从角色表中搜索带有 '$tag:1' 标记的角色	
	 * @return 以角色名字为 key，角色名字为 value 的数组
	 */
	public static function roleItemsWithTag($tag){
		$searchModel = new AuthItemSearch(['type' => Item::TYPE_ROLE]);
		$roles = $searchModel->search(null)->getModels();

		$items = [];
		foreach ($roles as $role) {
			if (isset($role->data)) {
				// $role->data array 类型
				$data = $role->data;				
				if (isset($data[$tag])) {
					// 将该 role 对应的 unique 数据保存
					$items[$role->name] = $role->name;
				}
			}
		}

		return $items;
	}	

	/**
	 *
	 * 赋予用户某个后台管理权限
	 * @param $oldRole string 旧角色
	 * @param $newRole string 新角色
	 * 
	 * 先删除旧角色，再添加新角色，支持清空用户旧角色功能
	 */
	
	public static function assignRole($roleName, $userId){		
		$model = new Assignment($userId);
		$manager = Yii::$app->getAuthManager();

		static::removeRole($userId);

		// 赋予新角色
		if (! empty($roleName)) {
			$roleItem = $manager->getRole($roleName);
			$manager->assign($roleItem, $userId);
			
			$config = Configs::instance();
			$cache = $config->cache;
			$cache->flush();
		}

	}
	/**
	 * 移除后台用户的角色
	 * @param $userId 用户Id
	 * 
	 */
	public static function removeRole($userId){
		$model = new Assignment($userId);
		if (empty($model)) {
			return;
		}

		$manager = Yii::$app->getAuthManager();

		// 删除所有已有旧角色
		$items = $model->getItems();

		$assigned = $items['assigned'];
		foreach ($assigned as $name => $value) {
			$item = $manager->getRole($name);
			if (! empty($item)) {
				$manager->revoke($item, $userId);
			}else{
				throw new Exception('用户后台身份里混杂了非角色部分: ' . $name, 1);
			}
		}
	}

	/**
	 * 返回以逗号分隔的用户所有角色描述字符串
	 *
	 * @param integer $userId
	 * @return string
	 */
	public static function roleDescriptionForUser(int $userId){
		$roles = static::roleForUser($userId);
		$desc = '';
		foreach ($roles as $role) {
				if (empty($desc)) {
						$desc = $role->name;
				}else{
						$desc .= ', ' . $role->name;
				}
		}

		return $desc;
	}
}

?>