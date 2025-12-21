/* Show dynamicaly taxonomies and terms */
(function ($) {
  "use strict";
  $(document).ready(function () {

    function updateSelectorsOnChange() {
      //remove previous terms selection

      var cus_term = $('#cus_terms');
      cus_term.val(null).trigger('change');

      var cus_terms_exclude = $('#cus_terms_exclude');
      cus_terms_exclude.val(null).trigger('change');

      //get the value of selected post type option
      var cus_post_type_selected = $('#cpt_post_type').find(':selected').val();

      //hide all taxonomy elements
      var cus_taxonomy = $('#cus_taxonomy');
      cus_taxonomy.find('option[value]').attr('disabled', 'disabled')

      var meta_terms1 = $('#meta_terms1');
      var meta_terms2 = $('#meta_terms2');
      meta_terms1.find('option[value]').attr('disabled', 'disabled');
      meta_terms2.find('option[value]').attr('disabled', 'disabled');

      //show taxonomies that start with post type selected
      cus_taxonomy.find('option[value="xxx__select_taxonomy"]').removeAttr('disabled')
      meta_terms1.find('option[value="xxx__select_taxonomy"]').removeAttr('disabled')
      meta_terms2.find('option[value="xxx__select_taxonomy"]').removeAttr('disabled')

      cus_taxonomy.find('option[value^=' + cus_post_type_selected + '__]').removeAttr('disabled')
      meta_terms1.find('option[value^=' + cus_post_type_selected + '__]').removeAttr('disabled')
      meta_terms2.find('option[value^=' + cus_post_type_selected + '__]').removeAttr('disabled')

      // select default value "Select Taxonomy"
      cus_taxonomy.val('xxx__select_taxonomy');
      meta_terms1.val('xxx__select_taxonomy');
      meta_terms2.val('xxx__select_taxonomy');
      //cus_taxonomy.trigger('change');
      cus_taxonomy.select2()
      meta_terms1.select2()
      meta_terms2.select2()
    }

    function showTaxonomiesOnclick() {
      //get the value of selected post type option
      var cus_post_type_selected = $('#cpt_post_type').find('option:selected').val();

      //hide all taxonomy elements
      var cus_taxonomy = $('#cus_taxonomy');
      cus_taxonomy.find('option[value]');

      cus_taxonomy.find('option[value]').attr('disabled', 'disabled');

      //show taxonomies that start with post type selected
      cus_taxonomy.find('option[value="xxx__select_taxonomy"]').removeAttr('disabled')
      cus_taxonomy.find('option[value^=' + cus_post_type_selected + '__]').removeAttr('disabled');

      cus_taxonomy.select2();
      cus_taxonomy.select2('open');
    }

    function showTerm1OnClick() {

      var cus_post_type_selected = $('#cpt_post_type').find('option:selected').val();

      var meta_terms1 = $('#meta_terms1');
      meta_terms1.find('option[value]');

      meta_terms1.find('option[value]').attr('disabled', 'disabled');
      meta_terms1.find('option[value="xxx__select_taxonomy"]').removeAttr('disabled');
      meta_terms1.find('option[value^=' + cus_post_type_selected + '__]').removeAttr('disabled');

      meta_terms1.select2();
      meta_terms1.select2('open');
    }

    function showTerm2OnClick() {

      var cus_post_type_selected = $('#cpt_post_type').find('option:selected').val();

      var meta_terms2 = $('#meta_terms2');
      meta_terms2.find('option[value]');

      meta_terms2.find('option[value]').attr('disabled', 'disabled');
      meta_terms2.find('option[value="xxx__select_taxonomy"]').removeAttr('disabled');
      meta_terms2.find('option[value^=' + cus_post_type_selected + '__]').removeAttr('disabled');

      meta_terms2.select2();
      meta_terms2.select2('open');
    }

    // fill in all the fields on post type change
    $(document).on('change', '#cpt_post_type', updateSelectorsOnChange);

    // fill in taxonomies on taxonomy click (to work with initial load)
    $(document).on('click', '#select2-cus_taxonomy-container', showTaxonomiesOnclick);
    $(document).on('click', '#select2-meta_terms1-container', showTerm1OnClick);
    $(document).on('click', '#select2-meta_terms2-container', showTerm2OnClick);

    //clear terms on taxonomy change
    $(document).on('change', '#cus_taxonomy', function () {

      var cus_terms = $('#cus_terms');
      cus_terms.val(null).trigger('change');

      var cus_terms_exclude = $('#cus_terms_exclude');
      cus_terms_exclude.val(null).trigger('change');
    });


    //show terms for selected taxonomy on click
    $(document).on('click', '#cus_terms + .select2-container', function () {

      var cus_taxonomy = $('#cus_taxonomy');
      var cus_terms = $('#cus_terms');

      //select terms where option starts with taxonomy text
      var cus_taxonomy_text = $('#cus_taxonomy').find('option:selected').text();

      if (cus_taxonomy_text == "Select Taxonomy") cus_taxonomy_text = 'select_taxonomy';

      cus_terms.find('option[value]').attr('disabled', 'disabled');
      cus_terms.find('option[value^=' + cus_taxonomy_text + '__]').removeAttr('disabled');
      cus_terms.select2();
      cus_terms.select2('open');
    });

    //show terms to exclude for selected taxonomy on click
    $(document).on('click', '#cus_terms_exclude + .select2-container', function () {

      var cus_taxonomy = $('#cus_taxonomy');
      var cus_terms_exclude = $('#cus_terms_exclude');

      //select terms where option starts with taxonomy text
      var cus_taxonomy_text = $('#cus_taxonomy').find('option:selected').text();
      if (cus_taxonomy_text == "Select Taxonomy") cus_taxonomy_text = 'select_taxonomy';

      cus_terms_exclude.find('option[value]').attr('disabled', 'disabled');
      cus_terms_exclude.find('option[value^=' + cus_taxonomy_text + '__]').removeAttr('disabled');
      cus_terms_exclude.select2();
      cus_terms_exclude.select2('open');

    });
  });
}(jQuery));
	 