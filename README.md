# PHP-GIT

This is a small project of mine. The main purpose is to support basic git commands on web server that have not git installed and it is not possible to change this in an easy way.

The main goals for this project are:
- use the same `.git` folders and files like git. This project can use existing git projects and git should use the managed projects of php-git without any compability issues.
- the command line commands should be identical to git. It is ok if not every command and option is implemented and supported by php-git but the supported functionality should be used in the same way.
- the command line output should mostly be identical to git. Small differences are ok (e.g. less options shown in the help command).
- Support of the following git features:
    - `git clone`
    - `git pull`
    - `git fetch`
    - detect merge conflicts

Some goals for the future:
- git submodules
- git commit + push
- other git commands
- integration with other php projects

## Current phase

For the first step I want to implement the git internal functions and the "git core". For this php-git needs to understand what git does with the files inside the `.git` folder and how to reproduce this.

The following commands are implemented:
- `git init`
- `git cat-file`
- `git hash-object`
- `git update-index`

The following core components are implemented:
- git blob objects
- git index
