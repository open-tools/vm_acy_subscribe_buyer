BASE=acy_subscribe_buyer
PLUGINTYPE=vmcustom
VERSION=1.0

PLUGINFILES=$(BASE).php $(BASE).script.php $(BASE).xml index.html

TRANSLATIONS=$(call wildcard,language/*/*.plg_$(PLUGINTYPE)_$(BASE).*ini) language/index.html $(call wildcard,language/*/index.html)
INDEXFILES=$(BASE)/index.html
TMPLFILES=$(call wildcard,$(BASE)/tmpl/*.php) $(BASE)/index.html $(BASE)/tmpl/index.html
ASSETS=$(call wildcard,$(BASE)/assets/*.png) $(call wildcard,$(BASE)/assets/*.css) 
# assets/index.html
ZIPFILE=plg_$(PLUGINTYPE)_$(BASE)_v$(VERSION).zip


zip: $(PLUGINFILES) $(TRANSLATIONS) $(ELEMENTS) $(TMPLFILES)
	@echo "Packing all files into distribution file $(ZIPFILE):"
	@zip -r $(ZIPFILE) $(PLUGINFILES) $(TRANSLATIONS) $(ELEMENTS) $(INDEXFILES) $(TMPLFILES) $(ASSETS)

clean:
	rm -f $(ZIPFILE)
