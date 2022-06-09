jQuery(document).ready(function($) {
    document.addEventListener( 'wpcf7mailsent', function( event ) {
		//Create variables
		var inputs = event.detail.inputs;
        var formid=event.detail.contactFormId;
        var clientname = "";
		var email = "";
		var paymenttype = "";
		var paymentoption = "";
		var frequency = "";
		var chargedate = "";
		var issuer = "";
        var description="NoDescriptionGiven";
        var skipRedirect = false;
        var url_string = window.location.href;
        var url = new URL(url_string);
        var page_id = "";
		var order_id = "";
		var amount = "";

		//Find the inputs based on their name
        for ( var i = 0; i < inputs.length; i++ ) {
            //console.log(inputs[i].name+"    " +inputs[i].value);
            if ((inputs[i].name).includes('paymentmethod') ) {
				console.log("Processing "+inputs[i].name)
				//Only use visible inputs
				var paymentbutons = document.getElementsByName(inputs[i].name);
				for ( var j = 0; j < paymentbutons.length; j++ ) {
					if ($(paymentbutons[j]).is(":visible")&paymentbutons[j].value==inputs[i].value){
						paymenttype = paymentbutons[j].placeholder;
						paymentoption = inputs[i].value;
					}
				}
				if (paymenttype == 'Not a mollie payment'){
                    skipRedirect = true;
					console.log("Not redirecting to mollie as non-mollie payment method is chosen");
                }
            }else if ((inputs[i].name).startsWith('issuer')){
				//Only use visible inputs
				var issuersselects = document.getElementsByName(inputs[i].name);
				for ( var j = 0; j < issuersselects.length; j++ ) {
					if ($(issuersselects[j]).is(":visible")){
						issuer = issuersselects[j].value;
					}
				} 
            }else if ((inputs[i].name).includes('amount')){
				if (inputs[i].value != ""){
					amount = inputs[i].value;
				}
			}else if ((inputs[i].name).includes('name')){
				if (inputs[i].value != ""){
					clientname = inputs[i].value;
				}
			}else if ((inputs[i].name).includes('orderid')){
				if (inputs[i].value != ""){
					order_id = inputs[i].value;
				}
			}else if ((inputs[i].name).includes('frequency')){
                frequency = inputs[i].value;
			}else if ((inputs[i].name).includes('chargedate')){
                chargedate = inputs[i].value;
			}else if ((inputs[i].name).includes('mail')){
				if (inputs[i].value != ""){
					email =inputs[i].value;
				}
            }else if ((inputs[i].name).includes('paymentdescription')){
                description =inputs[i].value;
            }
        }
		
		//Only work if their is an amount set
		if (amount == ""){
			skipRedirect = true;
			console.log("Not redirecting to mollie as there is no amount set.");
		}

		if (skipRedirect == false){
			//Send the values to the cf7_mollie_payment_handler function via AJAX
			var options   = {
				url :cf7_mollie_object.ajax_url,
				method: "POST",
				data: {
					"action"        : "getCheckOutURL",
					"page_id"       : page_id,
					"formid"		: formid,
					"amount"        : amount,
					"clientname"    : clientname,
					"order_id"    	: order_id,
					"email"        	: email,
					"paymenttype"   : paymenttype,
					"paymentoption" : paymentoption,
					"frequency"     : frequency,
					"chargedate"    : chargedate,
					"issuer"        : issuer,
					"description"   : description,
					"redirecturl"	: window.location.href,
				},
			}

			//Process the result
			$.ajax(options).done(function(success) {
				console.log(success);
				checkouturl = success.split("CheckOutURL=");
				console.log("Redirecting to "+checkouturl[checkouturl.length-1]);
				location = checkouturl[checkouturl.length-1];//redirect to the url returned
			}).fail(function(err) {
				console.log(arguments);
			});
		}
	});
});