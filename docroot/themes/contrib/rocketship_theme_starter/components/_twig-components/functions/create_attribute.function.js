'use strict';

/**
 * @param {} Twig
 */

const createAttributeTwigExtension = function (Twig) {
  Twig.extendFunction("create_attribute", function() {
    return {};
  });
};

export default createAttributeTwigExtension;
