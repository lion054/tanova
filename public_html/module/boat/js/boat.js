jQuery(function ($) {
    $(".bc_filter .g-filter-item").each(function () {
      $(this)
        .find(".bc-filter-price")
        .each(function () {
          var input_price = $(this).find(".filter-price");
          var min = input_price.data("min");
          var max = input_price.data("max");
          var from = input_price.data("from");
          var to = input_price.data("to");
          var symbol = input_price.data("symbol");
          input_price.ionRangeSlider({
            type: "double",
            grid: true,
            min: min,
            max: max,
            from: from,
            to: to,
            prefix: symbol,
          });
        });
    });
    $(".bc_form_filter input[type=checkbox]").change(function () {
        $(this).closest(".bc_form_filter").submit();
    });

});