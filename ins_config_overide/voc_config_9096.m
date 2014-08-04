function conf = voc_config_9096()
	conf.pascal.year = '9096';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9096/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9096/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9096/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9096/Images/%s.txt';
end