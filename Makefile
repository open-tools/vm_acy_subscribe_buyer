BASE=acy_subscribe_buyer
PLUGINTYPE=vmcustom
VERSION=1.0

PLUGINFILES=$(BASE).php $(BASE).script.php $(BASE).xml index.html

TRANSLATIONS=$(call wildcard,*.plg_$(PLUGINTYPE)_$(BASE).*ini) 
INDEXFILES=$(BASE)/index.html
TMPLFILES=$(call wildcard,$(BASE)/tmpl/*.php) $(BASE)/index.html $(BASE)/tmpl/index.html
ASSETS=$(call wildcard,assets/*.png) $(call wildcard,assets/*.css) 
# assets/index.html
ZIPFILE=plg_$(PLUGINTYPE)_$(BASE)_v$(VERSION).zip


zip: $(PLUGINFILES) $(TRANSLATIONS) $(ELEMENTS) $(TMPLFILES)
	@echo "Packing all files into distribution file $(ZIPFILE):"
	@zip -r $(ZIPFILE) $(PLUGINFILES) $(TRANSLATIONS) $(ELEMENTS) $(INDEXFILES) $(TMPLFILES) $(ASSETS)

clean:
	rm -f $(ZIPFILE)
