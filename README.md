extbase
=======

This extbase version is for TYPO3 4.7.
It includes many feature we have backported from TYPO3 6.2.
Including recursive validation, file upload and many more.

As long as TYPO3 6.2 is not public we can't upgrade one of our TYPO3 4.7 based projects.
But we need recursive validation which was implemented in TYPO3 6.1 and we love all the
new feature f.e. prependOptionLabel and sorting in f:form.select. There are many more
features implemented with new Property Mapper which are very useful. The array for images
are now merged with $_FILES in one parameter.

All these features keeps our code small and nice.

In a testing environment we have updated our project to TYPO3 6.2 Beta 1 now and removed
our backported extbase and fluid versions. All 25 self-made extensions, which works
previously with our self-made extbase/fluid versions runs to 95%. Most extensions run
out of the box, but some extension need some extra love on editing records in frontend
or wrong classnames in Domainmodels. Things which are done in less than 5 minutes :-)