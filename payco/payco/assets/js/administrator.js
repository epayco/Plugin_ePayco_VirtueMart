jQuery().ready(function ($) {

  var config = {

    custom_credid_card: ".mp-admin-checkout-custom-credid-card",
    custom_ticket: ".mp-admin-checkout-custom-ticket",
    custom: ".mp-admin-checkout-custom",
    basic: ".mp-admin-checkout-basic",

    selector: {
      type_integration: "select[name='params[payco_product_checkout]']"
    }
  }


  $(config.selector.type_integration).change(function () {
    handleTypeIntegration();
  });



  handleTypeIntegration = function(){
    var type_integration = $(config.selector.type_integration).val();
    switch (type_integration) {

      case "basic_checkout":
        $(config.custom_credid_card).parents(".control-group").hide();
        $(config.custom_ticket).parents(".control-group").hide();
        $(config.custom).parents(".control-group").hide();

        $(config.basic).parents(".control-group").show();
      break;
      case "custom_credit_card":
        $(config.basic).parents(".control-group").hide();
        $(config.custom_ticket).parents(".control-group").hide();

        //show config credic card
        $(config.custom).parents(".control-group").show();
        $(config.custom_credid_card).parents(".control-group").show();
      break;
      case "custom_ticket":
        $(config.custom_credid_card).parents(".control-group").hide();
        $(config.basic).parents(".control-group").hide();

        //show config ticket
        $(config.custom).parents(".control-group").show();
        $(config.custom_ticket).parents(".control-group").show();
      break;


    }
  }


  //force init form
  handleTypeIntegration();


  let checkboxes = document.querySelectorAll('input[name="params[opciones_pago][]"]');

  checkboxes.forEach(function (checkbox) {
    checkbox.addEventListener("click", function () {
      if (this.checked) {
        console.log("Seleccionaste:", this.value);
      } else {
        console.log("Deseleccionaste:", this.value);
      }
    });
  });

  checkboxes.forEach(function (checkbox) {
    checkbox.addEventListener("change", function () {
      let selected = [];
      checkboxes.forEach(function (cb) {
        if (cb.checked) {
          selected.push(cb.value);
        }
      });
      console.log("Métodos de pago seleccionados:", selected);
    });
  });

});
