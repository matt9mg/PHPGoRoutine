.PHONY: example
example:
	docker run --rm -it -v $(CURDIR):/app -w /app/examples phpswoole/swoole bash