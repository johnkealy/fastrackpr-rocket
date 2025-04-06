// Main Gulp file

// The gulp file has been broken down into smaller task files. Each task has its own js file.
// These files can be found in gulp/tasks.
// If you wish to add a new task, simply create a new js file in that directory.

var requireDir = require('require-dir');

// Require all tasks in gulp/tasks, including sub-folders.
requireDir('./gulp/tasks', { recurse: true });
