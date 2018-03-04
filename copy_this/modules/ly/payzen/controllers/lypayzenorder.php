<?php
/**
 * PayZen V2-Payment Module version 2.0.0 for OXID_eShop_CE 4.9.x. Support contact : support@payzen.eu.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  payment
 * @package   payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2016 Lyra Network and contributors
 * @license   http://www.gnu.org/licenses/gpl.html  GNU General Public License (GPL v3)
 */

class lyPayzenOrder extends lyPayzenOrder_parent
{
	/**
	 * Logger instance.
	 * @var lyPayzenLogger
	 */
	protected $_logger;

	/**
	 * Class constructor, initiates parent constructor (parent::oxBase()).
	 */
	public function __construct()
	{
		parent::__construct();

		$this->_logger = new lyPayzenLogger(__CLASS__);
	}

	/**
	 * Oxid payment flow.
	 *
	 * @param integer $iSuccess
	 * @return function
	 */
	protected function _getNextStep($iSuccess)
	{
		$oOrder = oxNew('lyPayzenOxOrder');
		$oOrder->load($this->getSession()->getVariable('sess_challenge'));

		if ($this->getPayment()->getId() !== 'oxidpayzen') {
			return parent::_getNextStep($iSuccess);
		}
		// step = 1 (new order) : redirect to PayZen server
		elseif ($iSuccess === oxOrder::ORDER_STATE_OK) {
			$oOrder->oxorder__oxtransstatus = new oxField('NOT_FINISHED');
			$oOrder->oxorder__oxsenddate = new oxField(date('Y-m-d H:i:s', time()), oxField::T_RAW);
			$oOrder->oxorder__oxip = new oxField($_SERVER['REMOTE_ADDR']);
			$oOrder->save();

			// redirect to the payment server
			return 'lypayzenredirect';
		}
		// step = 3 (last failed order OR order updated) : redirect to PayZen server
		elseif ($iSuccess === oxOrder::ORDER_STATE_ORDEREXISTS) {
			if(!$oOrder->getId()) {
				$oOrder->finalizeOrder($this->getSession()->getBasket(), $this->getUser(), true);
				$oOrder->oxorder__oxtransstatus = new oxField('NOT_FINISHED');
			}
			$oOrder->oxorder__oxsenddate = new oxField(date('Y-m-d H:i:s', time()), oxField::T_RAW);
			$oOrder->oxorder__oxip = new oxField($_SERVER['REMOTE_ADDR']);
			$oOrder->save();

			// redirect to the payment server
			return 'lypayzenredirect';
		}

		// continue with normal flow
		return parent::_getNextStep($iSuccess);
	}
}