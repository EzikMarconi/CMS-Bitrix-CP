<?
	use Bitrix\Main\Localization\Loc;
	Loc::loadMessages(__FILE__);
	$sum = roundEx($params['PAYMENT_SHOULD_PAY'], 2);
    CJSCore::Init(array("jquery"));
	$SITE_NAME = COption::GetOptionString("main", "server_name", "");
	$description= Loc::getMessage("VBCH_CLPAY_MM_DESC",array("#ORDER_ID#"=>$params['PAYMENT_ID'],"#SITE_NAME#"=>$SITE_NAME,"#DATE#"=>$params['PAYMENT_DATE_INSERT']));
?>

<?
if($params['CHECKONLINE']!='N'){
    \Bitrix\Main\Loader::includeModule("sale");
    \Bitrix\Main\Loader::includeModule("catalog");

    $order=\Bitrix\Sale\Order::load($params['PAYMENT_ID']);
    $basket = \Bitrix\Sale\Basket::loadItemsForOrder($order);
    $basketItems = $basket->getBasketItems();
    $data=array();
    $items=array();
    foreach ($basketItems as $basketItem) {
        $prD=\Bitrix\Catalog\ProductTable::getList(
            array(
                'filter'=>array('ID'=>$basketItem->getField('PRODUCT_ID')),
                'select'=>array('VAT_ID'),
            )
        )->fetch();
        if($prD){
            if($prD['VAT_ID']==0){
                $nds=null;
            }
            else{
                $nds=floatval($basketItem->getField('VAT_RATE'))==0 ? 0 : $basketItem->getField('VAT_RATE')*100;
            }
        }else{
            $nds=null;
        }

        $items[]=array(
                'label'=>$basketItem->getField('NAME'),
                'price'=>number_format($basketItem->getField('PRICE'),2,".",''),
                'quantity'=>$basketItem->getQuantity(),
                'amount'=>number_format(floatval($basketItem->getField('PRICE')*$basketItem->getQuantity()),2,".",''),
                'vat'=>$nds,
                'ean13'=>null
        );
    }
    
    //��������� ��������
    if ($order->getDeliveryPrice() > 0) 
    {
        $items[] = array(
            'label' => GetMessage('DELIVERY_TXT'),
            'price' => number_format($order->getDeliveryPrice(), 2, ".", ''),
            'quantity' => 1,
            'amount' => number_format($order->getDeliveryPrice(), 2, ".", ''),
            'vat' => null,
            'ean13' => null
        );
    }

    $data['cloudPayments']['customerReceipt']['Items']=$items;
    $data['taxationSystem']=$params['TYPE_NALOG'];
    $data['email']=$params['PAYMENT_BUYER_EMAIL'];
    $data['phone']=$params['PAYMENT_BUYER_PHONE'];
}
?>
<script src="https://widget.cloudpayments.ru/bundles/cloudpayments"></script>
<button class="cloudpay_button" id="payButton"><?=Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_CLOUDPAYMENTS_BUTTON_PAID')?></button>
<div id="result" style="display:none"></div>

<script type="text/javascript">
    var payHandler = function () {
        var widget = new cp.CloudPayments();
        widget.charge({ // options
                publicId: '<?=trim(htmlspecialcharsbx($params["APIPASS"]));?>',
                description: '<?=$description?>', 
                amount: <?=number_format($sum, 2, '.', '')?>,
                currency: '<?=$params['PAYMENT_CURRENCY']?>',
                email: '<?=$params['PAYMENT_BUYER_EMAIL']?>',
                invoiceId: '<?=htmlspecialcharsbx($params["PAYMENT_ID"]);?>',
                accountId: '<?=htmlspecialcharsbx($params["PAYMENT_BUYER_ID"]);?>',
            <?if($params['CHECKONLINE']!='N'){?>
                data: <?=CUtil::PhpToJSObject($data,false,true)?>,
            <?}?>

            },
            function (options) { // success
                BX("result").innerHTML="<?=GetMessage('VBCH_CLOUDPAY_SUCCESS')?>";
                BX.style(BX("result"),"color","green");
                BX.style(BX("result"),"display","block");
            },
            function (reason, options) { // fail
                BX("result").innerHTML=reason;
                BX.style(BX("result"),"color","red");
                BX.style(BX("result"),"display","block");
            });
    };
    $("#payButton").on("click", payHandler); //������ "��������"
</script>