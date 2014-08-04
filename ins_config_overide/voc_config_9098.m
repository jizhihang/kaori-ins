function conf = voc_config_9098()
	conf.pascal.year = '9098';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9098/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9098/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9098/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9098/Images/%s.txt';
end