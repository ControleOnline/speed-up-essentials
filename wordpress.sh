#! /bin/bash
# A modification of Dean Clatworthy's deploy script as found here: https://github.com/deanc/wordpress-plugin-git-svn
# The difference is that this script lives in the plugin's git repo & doesn't require an existing SVN repo.

# main config
PLUGINSLUG="speed-up-essentials-plugin"
CURRENTDIR=`pwd`
MAINFILE="speed-up-essentials-plugin.php" # this should be the name of your main php file in the wordpress plugin

# git config
GITPATH="$CURRENTDIR/" # this file should be in the base of your git repository

# svn config
SVNPATH="/tmp/$PLUGINSLUG" # path to a temp SVN repo. No trailing slash required and don't add trunk.
SVNURL="http://plugins.svn.wordpress.org/speedupessentials/" # Remote SVN repo on wordpress.org, with no trailing slash
SVNUSER="controleonline" # your svn username


# Let's begin...
echo ".........................................."
echo 
echo "Preparing to deploy wordpress plugin"
echo 
echo ".........................................."
echo 

# Check if subversion is installed before getting all worked up
if [ $(dpkg-query -W -f='${Status}' subversion 2>/dev/null | grep -c "ok installed") != "1" ]
then
	echo "You'll need to install subversion before proceeding. Exiting....";
	exit 1;
fi

# Check version in readme.txt is the same as plugin file after translating both to unix line breaks to work around grep's failure to identify mac line breaks

cd $GITPATH

echo 
echo "Creating local copy of SVN repo ..."
svn co $SVNURL $SVNPATH

#echo "Clearing svn repo so we can overwrite it"
#svn rm $SVNPATH/trunk/*

echo "Exporting the HEAD of master from git to the trunk of SVN"
git checkout-index -a -f --prefix=$SVNPATH/trunk/


echo "Changing directory to SVN and committing to trunk"
cd $SVNPATH/trunk/
# Add all new files that are not set to be ignored
svn status | grep -v "^.[ \t]*\..*" | grep "^?" | awk '{print $2}' | xargs svn add
svn commit --username=$SVNUSER

echo "Removing temporary directory $SVNPATH"
rm -fr $SVNPATH/

echo "*** FIM ***"