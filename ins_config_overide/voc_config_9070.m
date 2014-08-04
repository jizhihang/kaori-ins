function conf = voc_config_9070()
	conf.pascal.year = '9070';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9070/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9070/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9070/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9070/Images/%s.txt';
end