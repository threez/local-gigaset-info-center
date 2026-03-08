include config.mk

STAGE = .stage

.PHONY: publish

publish:
	rm -rf $(STAGE)
	mkdir -p $(STAGE)/info
	cp index.php weather.php .htaccess $(STAGE)/info/
	cp -r icons $(STAGE)/info/
	cp proxy.php $(STAGE)/
	cp gigaset-root.htaccess $(STAGE)/.htaccess
	cp -r icons $(STAGE)/img
	tar -C $(STAGE) -czf - . | ssh $(HOST) 'tar -C $(DEST) -xzf -'
	rm -rf $(STAGE)
