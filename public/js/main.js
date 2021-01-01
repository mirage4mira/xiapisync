/* eslint-disable object-shorthand */
/* global Chart, coreui, coreui.Utils.getStyle, coreui.Utils.hexToRgba */

/**
 * --------------------------------------------------------------------------
 * CoreUI Boostrap Admin Template (v3.0.0): main.js
 * Licensed under MIT (https://coreui.io/license)
 * --------------------------------------------------------------------------
 */

/* eslint-disable no-magic-numbers */
// Disable the on-canvas tooltip

//date format
var AJAX_DATE_FORMAT = "yyyy-M-d";
var DATEPICKER_DATE_FORMAT = "MM/dd/yyyy";
var EARLIEST_DATE = "01/01/1970";

function isInfinite(n) {
  return n === n/0;
}
//chart configs
if(typeof Chart !== 'undefined'){
  Chart.defaults.global.pointHitDetectionRadius = 1
  Chart.defaults.global.tooltips.enabled = false
  Chart.defaults.global.tooltips.mode = 'index'
  Chart.defaults.global.tooltips.position = 'nearest'
  Chart.defaults.global.tooltips.custom = coreui.ChartJS.customTooltips
  Chart.defaults.global.defaultFontColor = '#646470'
  Chart.defaults.global.responsiveAnimationDuration = 1
}

document.body.addEventListener('classtoggle', event => {
  if (event.detail.className === 'c-dark-theme') {
    if (document.body.classList.contains('c-dark-theme')) {
      cardChart1.data.datasets[0].pointBackgroundColor = coreui.Utils.getStyle('--primary-dark-theme')
      cardChart2.data.datasets[0].pointBackgroundColor = coreui.Utils.getStyle('--info-dark-theme')
      Chart.defaults.global.defaultFontColor = '#fff'
    } else {
      cardChart1.data.datasets[0].pointBackgroundColor = coreui.Utils.getStyle('--primary')
      cardChart2.data.datasets[0].pointBackgroundColor = coreui.Utils.getStyle('--info')
      Chart.defaults.global.defaultFontColor = '#646470'
    }

    cardChart1.update()
    cardChart2.update()
    mainChart.update()
  }
})

//handleErrors
var ajaxErrorResponse = function(jqXhr, json, errorThrown){// this are default for ajax errors 
  var errors = jqXhr.responseJSON;
  var errorsHtml = '';
  $.each(errors['errors'], function (index, value) {
      errorsHtml += '<ul class="list-group"><li class="list-group-item alert alert-danger">' + value + '</li></ul>';
  });
  var myhtml = document.createElement("div");
  myhtml.innerHTML = errorsHtml;
  //I use SweetAlert2 for this
  swal({
      title: "Error " + jqXhr.status + ': ' + errorThrown,// this will output "Error 422: Unprocessable Entity"
      content: myhtml,
      width: 'auto',
      confirmButtonText: 'Try again',
      cancelButtonText: 'Cancel',
      confirmButtonClass: 'btn',
      cancelButtonClass: 'cancel-class',
      showCancelButton: true,
      closeOnConfirm: true,
      closeOnCancel: true,
      type: 'error'
    }).then(function(isConfirm) {
    if (isConfirm) {
         $('#openModal').click();//this is when the form is in a modal
    }
});
};

function getProductsData(){
  return $.ajax({
      async: true,
      type: 'POST',
      url: '/inventory/get',
      data: {_token: CSRF_TOKEN},
      success: function(data) {
        console.log(data);
      },
      error: ajaxErrorResponse
  });
}

function getOrdersEscrowData(status,start_date,end_date){
  return $.ajax({
    async: true,
    type: 'POST',
    url: '/orders/get',
    data: {_token: CSRF_TOKEN,status,start_date,end_date},
    success: function(data) {
      console.log(data);
      data.forEach(function(d){
        if(d.buyer_username === "ungcat"){
          console.log(d)
        }
      })
    },
    error: ajaxErrorResponse
  });
}

function syncLatestData(obj){
  last_sync_time_exist = false;
  eraseCookie('last_sync_time');
  window.location.reload();
}

var last_sync_time_exist = false;
$(function(){
  setInterval(function(){

    if(getCookie('last_sync_time')){
      var start_time = parseInt(getCookie('last_sync_time') * 1000);
      var end_time = new Date().getTime();
      var difference = end_time - start_time;
      var resultInMinutes = Math.round(difference / 60000);
      if(resultInMinutes == 0){
        $('#sync-text').html('Last sync: just now');
      }else if(resultInMinutes < 60){
        $('#sync-text').html(`Last sync: ${resultInMinutes} ${resultInMinutes == 1?'minute' : 'minutes'} ago`);
      }else{
        var resultInHours = Math.floor(resultInMinutes/60);
        $('#sync-text').html(`Last sync: ${resultInHours} ${resultInHours == 1?'hour' : 'hours'} ago`);
      }
      last_sync_time_exist = true;
    }else{
      if(last_sync_time_exist == true){
        // $('#sync-text').html('Last sync: more than 30 minutes ago');
      }
    }
    // console.log(123);
  },1000 * 2 );
} )

function setCookie(name,value,days) {
  var expires = "";
  if (days) {
      var date = new Date();
      date.setTime(date.getTime() + (days*24*60*60*1000));
      expires = "; expires=" + date.toUTCString();
  }
  document.cookie = name + "=" + (value || "")  + expires + "; path=/";
}
function getCookie(name) {
  var nameEQ = name + "=";
  var ca = document.cookie.split(';');
  for(var i=0;i < ca.length;i++) {
      var c = ca[i];
      while (c.charAt(0)==' ') c = c.substring(1,c.length);
      if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
  }
  return null;
}
function eraseCookie(name) {   
  document.cookie = name +'=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
}

function initDoubleDatepicker(selector1,selector2){

  var date1 = $(selector1);
  var date2 = $(selector2);

  date1.datepicker().on('changeDate',function(){
    $(this).datepicker('hide');
  })
  date2.datepicker({
    orientation: "auto right"
  }).on('changeDate',function(){
    $(this).datepicker('hide');
  }) 

  date1.datepicker().on('hide',function(selectedDate){
    var date = $(this).datepicker("getDate");
            date2.datepicker("setDate", date);
            date2.datepicker( "show" );
  });

  date2.datepicker().on('hide',function(selectedDate){
    var date = $(this).datepicker({ dateFormat: 'mm/dd/yy' }).val();
    date1.val(date1.val() + " - " + date);
    date1.change();
  });
}

function money(money){
  return (money).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// eslint-disable-next-line no-unused-vars
// eslint-disable-next-line no-unused-vars

