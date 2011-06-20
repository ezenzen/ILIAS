<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Table/classes/class.ilTable2GUI.php");
include_once("./Modules/Blog/classes/class.ilObjBlog.php");

/**
 * Portfolio page table
 *
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 * @version $Id$
 *
 * @ingroup ServicesPortfolio
 */
class ilPortfolioPageTableGUI extends ilTable2GUI
{
	protected $portfolio;
	
	/**
	 * Constructor
	 */
	function __construct($a_parent_obj, $a_parent_cmd, ilPortfolio $a_portfolio)
	{
		global $ilCtrl, $lng, $ilAccess, $lng, $ilUser;

		$this->portfolio = $a_portfolio;

		parent::__construct($a_parent_obj, $a_parent_cmd);

		$this->setTitle($lng->txt("pages").": ".$a_portfolio->getTitle());

		$this->addColumn($this->lng->txt(""), "", "1");
		$this->addColumn($this->lng->txt("user_order"));
		$this->addColumn($this->lng->txt("title"));
		$this->addColumn($this->lng->txt("actions"));

		$this->setFormAction($ilCtrl->getFormAction($a_parent_obj));
		$this->setRowTemplate("tpl.portfolio_page_row.html", "Services/Portfolio");

		$this->addMultiCommand("confirmPortfolioPageDeletion", $lng->txt("delete"));
		$this->addCommandButton("savePortfolioPagesOrdering",
			$lng->txt("user_save_ordering_and_titles"));

		$this->getItems();
	}

	function getItems()
	{
		include_once("./Services/Portfolio/classes/class.ilPortfolioPage.php");
		$data = ilPortfolioPage::getAllPages($this->portfolio->getId());
		$this->setData($data);
	}

	/**
	 * Fill table row
	 */
	protected function fillRow($a_set)
	{
		global $lng, $lng, $ilCtrl;

		switch($a_set["type"])
		{
			case ilPortfolioPage::TYPE_PAGE:
				$this->tpl->setCurrentBlock("title_field");
				$this->tpl->setVariable("VAL_TITLE", ilUtil::prepareFormOutput($a_set["title"]));
				$this->tpl->parseCurrentBlock();
				
				$this->tpl->setCurrentBlock("action");
				$this->tpl->setVariable("TXT_EDIT", $lng->txt("edit"));
				$ilCtrl->setParameterByClass("ilportfoliopagegui",
					"ppage", $a_set["id"]);
				$this->tpl->setVariable("CMD_EDIT",
					$ilCtrl->getLinkTargetByClass("ilportfoliopagegui", "edit"));	
				$this->tpl->parseCurrentBlock();
				break;
			
			case ilPortfolioPage::TYPE_BLOG:
				$this->tpl->setCurrentBlock("title_static");
				$this->tpl->setVariable("VAL_TITLE", $lng->txt("obj_blog").": ".ilObjBlog::_lookupTitle($a_set["title"]));
				$this->tpl->parseCurrentBlock();
				break;
		}
		
		$this->tpl->setVariable("ID", $a_set["id"]);
		$this->tpl->setVariable("VAL_ORDER_NR", $a_set["order_nr"]);
	}
}

?>