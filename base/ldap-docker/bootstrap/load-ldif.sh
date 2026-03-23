#!/bin/bash
echo "Esperando que OpenLDAP inicie..."
until ldapsearch -x -H ldap://openldap -D "cn=admin,dc=una,dc=ac,dc=cr" -w admin -b "dc=una,dc=ac,dc=cr" "(objectClass=*)" dn &>/dev/null; do
  sleep 2
done

RESULT=$(ldapsearch -x -H ldap://openldap -D "cn=admin,dc=una,dc=ac,dc=cr" -w admin -b "ou=Groups,dc=una,dc=ac,dc=cr" "(objectClass=*)" dn 2>&1)
if echo "$RESULT" | grep -q "No such object"; then
  echo "Cargando estructura LDAP..."
  ldapadd -x -H ldap://openldap -D "cn=admin,dc=una,dc=ac,dc=cr" -w admin -f /tmp/estructura.ldif
  echo "Carga completa."
else
  echo "Estructura ya existe, omitiendo carga."
fi
