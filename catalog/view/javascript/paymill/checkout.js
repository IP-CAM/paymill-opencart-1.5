/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
var prefilled = new Array();
$(document).ready(function() {
    prefilled = getFormData(prefilled);
    $('#paymill_card_number').keyup(function() {
        var brand = paymill.cardType($('#paymill_card_number').val());
        brand = brand.toLowerCase();
        $('#paymill_card_number').prev("img").remove();
        switch (brand) {
            case 'visa':
                $('#paymill_card_number').before('<img src="' + PAYMILL_IMAGE + '/32x20_visa.png" >');
                break;
            case 'mastercard':
                $('#paymill_card_number').before('<img src="' + PAYMILL_IMAGE + '/32x20_mastercard.png" >');
                break;
            case 'american express':
                $('#paymill_card_number').before('<img src="' + PAYMILL_IMAGE + '/32x20_amex.png" >');
                break;
            case 'jcb':
                $('#paymill_card_number').before('<img src="' + PAYMILL_IMAGE + '/32x20_jcb.png" >');
                break;
            case 'maestro':
                $('#paymill_card_number').before('<img src="' + PAYMILL_IMAGE + '/32x20_maestro.png" >');
                break;
            case 'diners club':
                $('#paymill_card_number').before('<img src="' + PAYMILL_IMAGE + '/32x20_dinersclub.png" >');
                break;
            case 'discover':
                $('#paymill_card_number').before('<img src="' + PAYMILL_IMAGE + '/32x20_discover.png" >');
                break;
            case 'unionpay':
                $('#paymill_card_number').before('<img src="' + PAYMILL_IMAGE + '/32x20_unionpay.png" >');
                break;
            case 'unknown':
            default:
                $('#paymill_card_number').prev("img").remove();
                break;
        }
        $('#paymill_card_icon :first-child').css({
            "vertical-align": "middle",
            "margin-top": "-4px"
        });
    });

    $("#paymill_submit").click(function() {
        var formdata = new Array();
        formdata = getFormData(formdata);

        if (prefilled.toString() === formdata.toString()) {
            $("#paymill_form").append("<input type='hidden' name='paymillFastcheckout' value='" + true + "'/>");
            result = new Object();
            result.token = 'dummyToken';
            PaymillResponseHandler(null, result);
        } else {
            if (validate()) {
                try {
                    var params;
                    if (PAYMILL_PAYMENT === "paymillcreditcard") {
                        params = {
                            number: $('#paymill_card_number').val(),
                            cardholder: $('#paymill_account_holder').val(),
                            exp_month: $('#paymill_card_expiry_month').val(),
                            exp_year: $('#paymill_card_expiry_year').val(),
                            cvc: $('#paymill_card_cvc').val(),
                            amount_int: PAYMILL_AMOUNT,
                            currency: PAYMILL_CURRENCY
                        };
                    } else if (PAYMILL_PAYMENT === "paymilldirectdebit") {
                        params = {
                            number: $('#paymill_accountnumber').val(),
                            bank: $('#paymill_banknumber').val(),
                            accountholder: $('#paymill_accountholder').val()
                        };
                    }
                    paymill.createToken(params, PaymillResponseHandler);
                } catch (e) {
                    alert("Ein Fehler ist aufgetreten: " + e);
                }
            } else {
                $('html, body').animate({
                    scrollTop: $("#paymill_errors").offset().top - 100
                }, 1000);
            }
        }
        return false;
    });
});


function getFormData(array) {
    $('#paymill_form :input').not(':[type=hidden]').each(function() {
        array.push($(this).val());
    });
    return array;
}

function validate() {
    debug("Paymill handler triggered");
    var errors = $("#paymill_errors");
    errors.hide();
    errors.html("");
    var result = true;
    if (PAYMILL_PAYMENT === "paymillcreditcard") {
        if (!paymill.validateCardNumber($('#paymill_card_number').val())) {
            errors.append("<p>" + PAYMILL_TRANSLATION.paymill_card_number + "</p>");
            result = false;
        }
        if (!paymill.validateCvc($('#paymill_card_cvc').val())) {
            errors.append("<p>" + PAYMILL_TRANSLATION.paymill_card_cvc + "</p>");
            result = false;
        }
        if (!paymill.validateExpiry($('#paymill_card_expiry_month').val(), $('#paymill_card_expiry_year').val())) {
            errors.append("<p>" + PAYMILL_TRANSLATION.paymill_card_expiry_date + "</p>");
            result = false;
        }
        if (!$('#paymill_card_holder').val()) {
            errors.append("<p>" + PAYMILL_TRANSLATION.paymill_card_holder + "</p>");
            result = false;
        }
    } else if (PAYMILL_PAYMENT === "paymilldirectdebit") {
        if (!$('#paymill_accountholder').val()) {
            errors.append("<p>" + PAYMILL_TRANSLATION.paymill_accountholder + "</p>");
            result = false;
        }
        if (!paymill.validateAccountNumber($('#paymill_accountnumber').val())) {
            errors.append("<p>" + PAYMILL_TRANSLATION.paymill_accountnumber + "</p>");
            result = false;
        }
        if (!paymill.validateBankCode($('#paymill_banknumber').val())) {
            errors.append("<p>" + PAYMILL_TRANSLATION.paymill_banknumber + "</p>");
            result = false;
        }
    }
    if (!result) {
        errors.children().removeClass('warning').addClass('warning');
        errors.show();
    } else {
        debug("Validations successful");
    }
    return result;
}

function PaymillResponseHandler(error, result) {
    debug("Started Paymill response handler");
    if (error) {
        debug("API returned error:" + error.apierror);
        alert("API returned error:" + error.apierror);
        $(".checkout-heading").children('a :last').click(); //click on step5 Modify
    } else {
        debug("Received token from Paymill API: " + result.token);
        var form = $("#paymill_form");
        var token = result.token;
        form.append("<input type='hidden' name='paymillToken' value='" + token + "'/>");
        form.get(0).submit();
    }
}

function debug(message) {
    if (PAYMILL_DEBUG) {
        if (PAYMILL_PAYMENT === "paymillcreditcard") {
            console.log("[PaymillCC] " + message);
        } else if (PAYMILL_PAYMENT === "paymilldirectdebit") {
            console.log("[PaymillELV] " + message);
        }
    }
}