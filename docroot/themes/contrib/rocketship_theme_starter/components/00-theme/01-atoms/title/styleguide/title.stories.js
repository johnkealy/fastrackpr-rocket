import React from 'react';
import title from './title.twig';
import titleData from './title.yml';

// Documentation.
import mdx from './title.mdx';

/**
 * Storybook Definition.
 */
export default {
  title: 'Atoms/Entity page title',
  parameters: { docs: {
  page: mdx // Needed to load an mdx file for documentation: componentName.mdx.
  } },
};

export const entityPageTitle = () => (
  <div dangerouslySetInnerHTML={{ __html: title(titleData) }}/>
);