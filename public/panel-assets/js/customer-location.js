(function ($) {
  'use strict';

  var locations = window.customerLocations || {};

  function initSelect2($select, placeholder) {
    if ($select.hasClass('select2-hidden-accessible')) {
      $select.select2('destroy');
    }

    $select.select2({
      width: '100%',
      placeholder: placeholder,
      allowClear: true,
    });
  }

  function fillDistricts($district, region, selected) {
    var districts = locations[region] || [];
    var html = '<option value=""></option>';

    districts.forEach(function (district) {
      var sel = district === selected ? ' selected' : '';
      html += '<option value="' + district + '"' + sel + '>' + district + '</option>';
    });

    $district.html(html);
    initSelect2($district, 'Search district');

    if (selected) {
      $district.val(selected).trigger('change.select2');
    }
  }

  $(function () {
    var $region = $('#customerRegion');
    var $district = $('#customerDistrict');

    if (!$region.length || !$district.length) {
      return;
    }

    initSelect2($region, 'Search region');

    var initialRegion = $region.val();
    var initialDistrict = $district.data('selected') || '';

    if (initialRegion) {
      fillDistricts($district, initialRegion, initialDistrict);
    } else {
      initSelect2($district, 'Search district');
    }

    $region.on('change', function () {
      fillDistricts($district, $(this).val(), '');
    });
  });
})(jQuery);
