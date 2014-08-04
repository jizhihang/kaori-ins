function conf = voc_config_9081()
	conf.pascal.year = '9081';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9081/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9081/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9081/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9081/Images/%s.txt';
end