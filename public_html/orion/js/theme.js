// js Document

    // Project Name : Orion -- Real Estate Crowdinvesting;
    // Version      : 1.0.
    // Developed by : Heloshape (heloshape18@gmail.com)


(function($) {
    "use strict";
    
    
    $(document).on ('ready', function (){
        
        // -------------------- Navigation Scroll
        $(window).on('scroll', function (){   
          var sticky = $('.sticky-menu'),
          scroll = $(window).scrollTop();
          if (scroll >= 100) sticky.addClass('fixed');
          else sticky.removeClass('fixed');

        });
        
        // -------------------------- Modal Toggle Classs
        $(document).on('click', '[data-toggle-class]', function (e) {
          var $self = $(this);
          var attr = $self.attr('data-toggle-class');
          var target = $self.attr('data-toggle-class-target') || $self.attr('data-target');
          var closest = $self.attr('data-target-closest');
          var classes = ( attr && attr.split(',')) || '',
            targets = (target && target.split(',')) || Array($self),
            key = 0;
          $.each(classes, function( index, value ) {
            var target = closest ? $self.closest(targets[(targets.length == 1 ? 0 : key)]) : $( targets[(targets.length == 1 ? 0 : key)] ),
                      current = target.attr('data-class'),
                      _class = classes[index];
                  (current != _class) && target.removeClass( target.attr('data-class') );
            target.toggleClass(classes[index]);
            target.attr('data-class', _class);
            key++;
          });
          $self.toggleClass('active');
          $self.attr('href') == "#" ? e.preventDefault() : '';
        });


        // ------------------- Modal Tabs
        if ($('.modal-content').length) { 
          $('.continue-button').on('click', function () {
            $('.modal-navs > li .active').parent('li').next('li').find('a').trigger('click');
            });
          $('.back-button').on('click', function () {
              $('.modal-navs > li .active').parent('li').prev('li').find('a').trigger('click');
          });
        }

        // ------------------- Mobile Number Select Dropdown
        if ($('#phone').length) {
          $("#phone").intlTelInput({
            utilsScript: "vendor/intl-tel/build/js/utils.js"
          });
        }

        // ------------------- Country Select Dropdown
        if ($('.countryInput').length) {
          $(".countryInput").countrySelect();
        }
        
        // ---------------------------- Greeting Message
        if ($('.greeting-text').length) { 
          var thehours = new Date().getHours();
          var themessage;
          var morning = ('Good morning');
          var afternoon = ('Good afternoon');
          var evening = ('Good evening');

          if (thehours >= 0 && thehours < 12) {
            themessage = morning; 

          } else if (thehours >= 12 && thehours < 17) {
            themessage = afternoon;

          } else if (thehours >= 17 && thehours < 24) {
            themessage = evening;
          }
          $('.greeting').append(themessage);
        }
          


        // -------------------- Remove Placeholder When Focus Or Click
        $("input,textarea").each( function(){
            $(this).data('holder',$(this).attr('placeholder'));
            $(this).on('focusin', function() {
                $(this).attr('placeholder','');
            });
            $(this).on('focusout', function() {
                $(this).attr('placeholder',$(this).data('holder'));
            });     
        });


        // ----------------------------- Counter Function
        var timer = $('.timer');
        if(timer.length) {
            timer.appear(function () {
              timer.countTo();
          });
        }


        // ----------------------- Tooltips
        $('[data-toggle="tooltip"]').tooltip()



        // -------------------------------- Accordion Panel
          if ($('.theme-accordion > .panel').length) {
            $('.theme-accordion > .panel').on('show.bs.collapse', function (e) {
                  var heading = $(this).find('.panel-heading');
                  heading.addClass("active-panel");
                  
            });
            $('.theme-accordion > .panel').on('hidden.bs.collapse', function (e) {
                var heading = $(this).find('.panel-heading');
                  heading.removeClass("active-panel");
                  //setProgressBar(heading.get(0).id);
            });
          }


          // ---------------------------- Select Dropdown
           if($('select').length) {
            $('.theme-select-menu').selectize();
         }

          // ---------------------------- Join account Checkbox
          if($('#joinAccount').length) {
            $("#joinAccount").on('click', function () {
              $(".jointAccountContainer").toggleClass('open')
            }) 
          }


          // ---------------------------- Filter Dropdown
          if($(".mobile-filter-button").length) {
            $(".mobile-filter-button").on('click', function () {
              $(".xl-content-visible").toggleClass('show')
            }) 
          }


          // ---------------------------- Filter Dropdown
          if($(".filter-button").length) {
            $(".filter-button").on('click', function () {
              $(this).toggleClass('open');
              $(".filter-open-menu").slideToggle(300);
            }) 
          }

          // ---------------------------- Company SignUp
          if($(".tbutton2").length) {
            $(".tbutton2").on('click', function () {
              $(".show-for-company").addClass('show');
              $(".hide-for-company").addClass('hide');
            });
            $(".tbutton1").on('click', function () {
              $(".show-for-company").removeClass('show');
              $(".hide-for-company").removeClass('hide');
            });
          }


          // ---------------------------- Profile Edit Settings
          if($(".edit-profile-button").length) {
            $(".edit-profile-button").on('click', function () {
              $("#personal-info-form").addClass('show');
              $(".personal-info-update").addClass('hide');
            });
            $(".save-profile-button").on('click', function () {
              $("#personal-info-form").removeClass('show');
              $(".personal-info-update").removeClass('hide');
            });
          }



          // ---------------------------- Investment amount
          if ($('.amount-ranger').length) {
            $( '.amount-ranger #slider-range' ).slider({
              range: true,
              min: 25000,
              max: 1000000,
              values: [ 25000, 1000000],
              slide: function( event, ui ) {
                $( '.ranger-min-max-block .min' ).val( '$' + ui.values[ 0 ] );
              }
            });
              $( '.ranger-min-max-block .min' ).val( '$' + $( '#slider-range' ).slider( 'values', 0 ) );        
        };


        // ---------------------------- Loan Calculate
          if ($('.loan-calculate').length) {
            $( '.loan-calculate #slider-range' ).slider({
              range: true,
              min: 5,
              max: 15,
              step: 5,
              values: [ 5,15],
              slide: function( event, ui ) {
                $( '.ranger-min-max-block .min' ).val( ui.values[ 0 ] );
                
                //calculate return
                
                let amount = parseFloat($('#principal').val());
                let projectYield = parseFloat($('#yield').val());
                let annualRate = parseFloat($('#annualRate').val());
                
                let years = ui.values[ 0 ];
                
                let theYield = (projectYield*0.01)*amount;
                let dividend = years*((annualRate/100)*amount);
                
                let roi = amount + theYield + dividend;
                
                var nf = new Intl.NumberFormat();
                
                
                $('.return-value').val(nf.format(roi));
              }
            });
              $( '.ranger-min-max-block .min' ).val(  $( '#slider-range' ).slider( 'values', 0 ) ); 
              
        };


        // ------------------------ Checkbox select
        if($(".docfile_up_radio").length) {
          $('.docfile_up_radio').on('click', function () {
            // left side data select and exits remove class
            $('.docfile_up_radio').removeClass('select');
            $(this).addClass('select');

            //right side data hide and how 
            $('.filedoc_of_identy').removeClass('show');
              var showid=$(this).data('id');
              $('#'+showid).addClass('show');
          });
        }
        

        
    });

    
    $(window).on ('load', function (){ // makes sure the whole site is loaded

        // -------------------- Site Preloader
        $('#loader').fadeOut(); // will first fade out the loading animation
        $('#loader-wrapper').delay(350).fadeOut('slow'); // will fade out the white DIV that covers the website.
        $('body').delay(350).css({'overflow':'visible'});

        // --------------- Chart One
            if($('.graph').length) { 
                google.charts.load('current', {packages: ['corechart', 'bar']});
                google.charts.setOnLoadCallback(drawBasic);

                function drawBasic() {
                    var data = google.visualization.arrayToDataTable([
                        ['Month', 'FacturaciÃ³n', { role: 'style' }],
                        ['Monthly In', 2200, '#285050'],            // RGB value
                        ['Monthly Out', 0, '#fff'],            // Define the color for Monthly Out color
                    ]);
                    var options = {
                        vAxis: {format: 'currency'},
                        legend: { position: 'none' },
                        bar: {groupWidth: "100%"}
                          
                        };
                    var chart = new google.visualization.ColumnChart(
                        document.getElementById('chart_one'));
                        chart.draw(data, options);
                }
            }

    });


    
})(jQuery);


;(function($) {
    "use strict";  
    
    //* Form js
    function verificationForm(){
        //jQuery time
        var current_fs, next_fs, previous_fs; //fieldsets
        var left, opacity, scale; //fieldset properties which we will animate
        var animating; //flag to prevent quick multi-click glitches

        $(".next").click(function () {
            if (animating) return false;
            animating = true;

            current_fs = $(this).parent();
            next_fs = $(this).parent().next();

            //activate next step on progressbar using the index of next_fs
            $("#progressbar li").eq($("fieldset").index(next_fs)).addClass("active");

            //show the next fieldset
            next_fs.show();
            //hide the current fieldset with style
            current_fs.animate({
                opacity: 0
            }, {
                step: function (now, mx) {
                    //as the opacity of current_fs reduces to 0 - stored in "now"
                    //1. scale current_fs down to 80%
                    scale = 1 - (1 - now) * 0.2;
                    //2. bring next_fs from the right(50%)
                    left = (now * 50) + "%";
                    //3. increase opacity of next_fs to 1 as it moves in
                    opacity = 1 - now;
                    current_fs.css({
                        'transform': 'scale(' + scale + ')',
                        'position': 'absolute'
                    });
                    next_fs.css({
                        'left': left,
                        'opacity': opacity
                    });
                },
                duration: 800,
                complete: function () {
                    current_fs.hide();
                    animating = false;
                },
                //this comes from the custom easing plugin
                easing: 'easeInOutBack'
            });
        });

        $(".previous").click(function () {
            if (animating) return false;
            animating = true;

            current_fs = $(this).parent();
            previous_fs = $(this).parent().prev();

            //de-activate current step on progressbar
            $("#progressbar li").eq($("fieldset").index(current_fs)).removeClass("active");

            //show the previous fieldset
            previous_fs.show();
            //hide the current fieldset with style
            current_fs.animate({
                opacity: 0
            }, {
                step: function (now, mx) {
                    //as the opacity of current_fs reduces to 0 - stored in "now"
                    //1. scale previous_fs from 80% to 100%
                    scale = 0.8 + (1 - now) * 0.2;
                    //2. take current_fs to the right(50%) - from 0%
                    left = ((1 - now) * 50) + "%";
                    //3. increase opacity of previous_fs to 1 as it moves in
                    opacity = 1 - now;
                    current_fs.css({
                        'left': left
                    });
                    previous_fs.css({
                        'transform': 'scale(' + scale + ')',
                        'opacity': opacity
                    });
                },
                duration: 800,
                complete: function () {
                    current_fs.hide();
                    animating = false;
                },
                //this comes from the custom easing plugin
                easing: 'easeInOutBack'
            });
        });

        $(".submit").click(function () {
            return false;
        })
    }; 
    

    /*Function Calls*/  
    verificationForm ();
})(jQuery); 