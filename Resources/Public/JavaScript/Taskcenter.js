import $ from 'jquery';
import DocumentService from '@typo3/core/document-service.js';
import Icons from '@typo3/backend/icons.js';
DocumentService.ready().then(() => {
  /**
   *
   * @type {{}}
   * @exports  TYPO3/CMS/Taskcenter/Taskcenter
   */
  var Taskcenter = {};


  /**
   *
   * @param {Object} element
   * @param {Boolean} isCollapsed
   */
  Taskcenter.collapse = function(element, isCollapsed) {
    var $item = $(element);
    var $parent = $item.parent();
    var $icon = $parent.find('.t3js-taskcenter-header-collapse .t3js-icon');
    var iconName;

    if (isCollapsed) {
      iconName = 'actions-view-list-expand';
    } else {
      iconName = 'actions-view-list-collapse';
    }
    Icons.getIcon(iconName, Icons.sizes.small, null, null, 'inline').then(function(icon) {
      $icon.replaceWith(icon);
    });

    $.ajax({
      url: TYPO3.settings.ajaxUrls['taskcenter_collapse'],
      type: 'post',
      cache: false,
      data: {
        'item': $parent.data('taskcenterId'),
        'state': isCollapsed
      }
    });
  };



  /**
   * Register listeners
   */
  Taskcenter.initializeEvents = function() {
    $('.t3js-taskcenter-collapse').on('show.bs.collapse', function() {
      Taskcenter.collapse($(this), 0);
    });
    $('.t3js-taskcenter-collapse').on('hide.bs.collapse', function() {
      Taskcenter.collapse($(this), 1);
    });
  };

  $(Taskcenter.initializeEvents);

  return Taskcenter;
});

