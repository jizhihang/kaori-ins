function conf = voc_config_9082()
	conf.pascal.year = '9082';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9082/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9082/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9082/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9082/Images/%s.txt';
end