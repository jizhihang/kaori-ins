function conf = voc_config_9080()
	conf.pascal.year = '9080';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9080/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9080/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9080/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9080/Images/%s.txt';
end