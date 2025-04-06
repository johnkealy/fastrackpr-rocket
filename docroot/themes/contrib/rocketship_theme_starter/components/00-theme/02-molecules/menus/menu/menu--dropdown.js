/**
 * Menu dropdown logic.
 */

(function (Drupal, window, document) {
  "use strict";

  ///////////////////////////////////////////////////////////////////////
  // Behavior for Menu dropdown: functions.
  ///////////////////////////////////////////////////////////////////////
  class RSdropdownMenu {
    constructor(menu) {
      this.toggleClass = 'js-open';
      this.menuObj = menu;
      this.controlledNodes = [];
      this.openIndex = null;
      this.topLevelNodes = [
        ...this.menuObj.querySelectorAll(
          ':scope > .menu__item--with-sub > .menu__link, button[aria-expanded][aria-controls]'
        ),
      ];
      this.topLevelNodes.forEach((node) => {
        // Handle button + menu.
        if (
          node.tagName.toLowerCase() === 'button' &&
          node.hasAttribute('aria-controls')
        ) {
          const menu = node.parentNode.querySelector('ul');
          if (menu) {
            // Save ref controlled menu.
            this.controlledNodes.push(menu);

            // Collapse menus.
            node.setAttribute('aria-expanded', 'false');
            this.toggleMenu(menu, false);

            // Attach event listeners.
            menu.addEventListener('keydown', this.onMenuKeyDown.bind(this));
            node.addEventListener('click', this.onButtonClick.bind(this));
            node.addEventListener('keydown', this.onButtonKeyDown.bind(this));

            // Disable tabbing.
            node.nextElementSibling.querySelectorAll('a').forEach((menuItem) => {
              menuItem.tabIndex = -1;
            });
          }
        }
        else {
          // Handle links.
          this.controlledNodes.push(null);
          node.addEventListener('keydown', this.onLinkKeyDown.bind(this));
          node.addEventListener('mouseenter', this.onButtonMouseHover.bind(this));
          node.addEventListener('mouseleave', this.onButtonMouseHover.bind(this));
        }
      });
    }

    controlFocusByKey(e, nodeList, currentIndex) {
      let flag = false;
      switch (e.key) {
        case 'ArrowUp':
        case 'ArrowLeft':
          flag = true;
          if (currentIndex > -1) {
            var prevIndex = Math.max(0, currentIndex - 1);
            nodeList[prevIndex].focus();
          }
          break;
        case 'ArrowDown':
        case 'ArrowRight':
          flag = true;
          if (currentIndex > -1) {
            var nextIndex = Math.min(nodeList.length - 1, currentIndex + 1);
            nodeList[nextIndex].focus();
          }
          break;
        case 'Home':
          flag = true;
          nodeList[0].focus();
          break;
        case 'End':
          flag = true;
          nodeList[nodeList.length - 1].focus();
          break;
        case 'Tab':
          setTimeout(() => {
            // Check if current menus that are not this item have active class.
            if (this.openIndex) {
              if (!this.controlledNodes[this.openIndex].parentNode.contains(document.activeElement)) {
                this.toggleExpand(this.openIndex);
              }
            }
          });

      }

      if (flag) {
        e.preventDefault();
      }
    }

    close() {
      this.toggleExpand(this.openIndex, false);
    }

    onButtonClick(e) {
      const button = e.currentTarget;

      const buttonIndex = this.topLevelNodes.indexOf(button);
      const buttonExpanded = button.getAttribute('aria-expanded') === 'true';

      this.toggleExpand(buttonIndex, !buttonExpanded);
    }
    onButtonMouseHover(e) {
      if (document.body.scrollWidth < 940) {
        if (e.type === 'mouseenter' || e.type === 'mouseleave') {
          return;
        }
      }

      let button = e.currentTarget;

      if (button.tagName !== 'BUTTON') {
        button = button.parentNode.querySelector('button[aria-expanded][aria-controls]');
      }

      const buttonIndex = this.topLevelNodes.indexOf(button);

      if (e.type === 'mouseenter') {
        return this.toggleExpand(buttonIndex, true);
      }

      return this.toggleExpand(buttonIndex, false);
    }
    onButtonKeyDown(e) {
      const targetButtonIndex = this.topLevelNodes.indexOf(document.activeElement);

      // Close on escape.
      if (e.key === 'Escape') {
        this.toggleExpand(this.openIndex, false);
      }
      else if (
        // Move focus into the open menu if the current menu is open.
        this.openIndex === targetButtonIndex &&
        e.key === 'ArrowDown'
      ) {
        e.preventDefault();
        this.controlledNodes[this.openIndex].querySelector('a').focus();
      }
      else {
        // Handle arrow key navigation between top-level buttons, if set.
        this.controlFocusByKey(e, this.controlledNodes[targetButtonIndex].querySelectorAll(':scope > li > a'), targetButtonIndex);
      }
    }
    onLinkKeyDown(e) {
      const targetLinkIndex = this.topLevelNodes.indexOf(document.activeElement);

      // Handle arrow key navigation between top-level buttons, if set.
      this.controlFocusByKey(e, this.topLevelNodes, targetLinkIndex);
    }
    onMenuKeyDown(e) {
      if (this.openIndex === null) {
        return;
      }

      const menuLinks = Array.prototype.slice.call(
        this.controlledNodes[this.openIndex].querySelectorAll('a')
      );
      const currentIndex = menuLinks.indexOf(document.activeElement);

      // Close on escape.
      if (e.key === 'Escape') {
        this.topLevelNodes[this.openIndex].focus();
        this.toggleExpand(this.openIndex, false);
      }
      else {
        this.controlFocusByKey(e, menuLinks, currentIndex);
        // Handle arrow key navigation within menu links, if set.
      }
    }
    toggleExpand (index, expanded) {
      this.openIndex = expanded ? index : null;
      const currentMenu = this.controlledNodes[index];

      if (index) {
        this.topLevelNodes[index].setAttribute('aria-expanded', expanded);
      }
      this.toggleMenu(currentMenu, expanded);
    }
    toggleMenu(domNode, show){
      if (!domNode) {
        return;
      }
      if (show) {
        domNode.classList.add(this.toggleClass);
        domNode.querySelectorAll('a').forEach((menuItem) => {
          menuItem.removeAttribute('tabindex');
        });
      }
      else {
        domNode.classList.remove(this.toggleClass);

        // Close all sub items too.
        domNode.querySelectorAll(`.${this.toggleClass}`).forEach((item) => {
          item.classList.remove(this.toggleClass);
        });
        domNode.querySelectorAll('[aria-expanded="true"]').forEach((item) => {
          item.setAttribute('aria-expanded', 'false');
        });

        domNode.querySelectorAll('a').forEach((menuItem) => {
          menuItem.tabIndex = -1;
        });
      }
    }
  }

  ///////////////////////////////////////////////////////////////////////
  // Behavior for Tabs: triggers.
  ///////////////////////////////////////////////////////////////////////

  Drupal.behaviors.rocketshipUIDropdown = {
    attach: function (context) {
      const navigationItems = '.rsDropdownMenu';

      once('dropdown', navigationItems, context).forEach((menu) => {
        const dropdownMenu = new RSdropdownMenu(menu.querySelector(':scope > ul'));
      });
    }
  };
})(Drupal, window, document);
