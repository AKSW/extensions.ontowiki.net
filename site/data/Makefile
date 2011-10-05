options=--inputOptions="-i turtle"
OWCLIDATA=owcli -w extensions.ontowiki.net -m http://extensions.ontowiki.net/ ${options}
OWCLISCHEMA=owcli -w extensions.ontowiki.net -m http://extensions.ontowiki.net/ ${options}
OWCLICONFIG=owcli -w extensions.ontowiki.net -m http://localhost/OntoWiki/Config/ ${options}
ALLTMP="/tmp/extensions.ontowiki.net.n3"

default:
	@echo "no default target"

install: clean create install-all

install-all:
	rm -rf ${ALLTMP}
	cp prefixes.n3 ${ALLTMP}
	cat *.n3 | grep -v "@prefix" >>${ALLTMP}
	${OWCLIDATA} -e model:add -i ${ALLTMP}

clean:
	${OWCLIDATA} -e model:drop

create:
	${OWCLIDATA} -e model:create
	${OWCLICONFIG} -e model:add -i config.ttl

