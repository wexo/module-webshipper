<?php

namespace Wexo\Webshipper\Model\ResourceModel;

class OrderLog extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
	{
		$this->_init('webshipper', 'id');
	}
}
