#View the project's README.md for invocation/maintenance rules.

AC_PREREQ([2.69])
AC_INIT([apigateway-generic-php-library], [1.0.0], [jhollenbeck@nabancard.com])

#make sure we have the dir where the bulk of the service-building logic is.
AC_CONFIG_SRCDIR([DependencyInjection])

# Check for custom binaries that we require for the make commands
AC_CHECK_PROG(DOCKER_BIN, docker, yes)
if test x"$DOCKER_BIN" != x"yes" ; then
    AC_MSG_ERROR([Docker was not found in your PATH. Please install.])
fi

AC_CONFIG_FILES([Makefile])
AC_OUTPUT
