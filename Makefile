test: vendor
	vendor/bin/phing test

vendor: composer.json
	composer update --prefer-dist
	touch $@

.PHONY: test
