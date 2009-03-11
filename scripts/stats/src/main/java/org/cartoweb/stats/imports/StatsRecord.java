/*
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * @copyright 2008 Camptocamp SA
 */

package org.cartoweb.stats.imports;

import org.pvalsecc.jdbc.Column;
import org.pvalsecc.jdbc.Entity;

import java.sql.Timestamp;
import java.util.List;


/**
 * Represents one record created in the DB during the import.
 */
@Entity(name = "stats")
public class StatsRecord implements Cloneable {
    @Column
    private long id;

    @Column
    private Integer generalBrowserInfo;

    @Column
    private Integer exportpdfFormat;

    @Column
    private String generalIp;

    @Column
    private int generalMapid;

    @Column
    private Integer imagesMainmapHeight;

    @Column
    private Integer imagesMainmapSize;

    @Column
    private boolean generalDirectAccess;

    @Column
    private Integer generalSecurityUser;

    @Column
    private String generalCacheId;

    @Column
    private Float generalElapsedTime;

    @Column
    private Integer generalExportPlugin;

    @Column
    private Integer generalUa;

    @Column
    private Integer queryResultsCount;

    @Column
    private String queryResultsTableCount;

    @Column
    private String generalCacheHit;

    @Column
    private Float locationScale;

    @Column
    private Integer generalSessid;

    @Column
    private Integer imagesMainmapWidth;

    @Column
    private Integer exportpdfResolution;

    @Column
    private Timestamp generalTime;

    @Column
    private double bboxMinx;

    @Column
    private double bboxMiny;

    @Column
    private double bboxMaxx;

    @Column
    private double bboxMaxy;

    @Column
    private Integer layersSwitchId;

    @Column
    private Integer generalClientVersion;

    @Column
    private String layers;

    @Column
    private String generalRequestId;

    private transient List<Integer> layerArray;

    public long getId() {
        return id;
    }

    public void setId(long id) {
        this.id = id;
    }

    public Integer getGeneralBrowserInfo() {
        return generalBrowserInfo;
    }

    public void setGeneralBrowserInfo(Integer generalBrowserInfo) {
        this.generalBrowserInfo = generalBrowserInfo;
    }

    public Integer getExportpdfFormat() {
        return exportpdfFormat;
    }

    public void setExportpdfFormat(Integer exportpdfFormat) {
        this.exportpdfFormat = exportpdfFormat;
    }

    public String getGeneralIp() {
        return generalIp;
    }

    public void setGeneralIp(String generapIp) {
        this.generalIp = generapIp;
    }

    public int getGeneralMapid() {
        return generalMapid;
    }

    public void setGeneralMapid(int generalMapid) {
        this.generalMapid = generalMapid;
    }

    public Integer getImagesMainmapHeight() {
        return imagesMainmapHeight;
    }

    public void setImagesMainmapHeight(Integer imagesMainmapHeight) {
        this.imagesMainmapHeight = imagesMainmapHeight;
    }

    public boolean isGeneralDirectAccess() {
        return generalDirectAccess;
    }

    public void setGeneralDirectAccess(boolean generalDirectAccess) {
        this.generalDirectAccess = generalDirectAccess;
    }

    public Integer getGeneralSecurityUser() {
        return generalSecurityUser;
    }

    public void setGeneralSecurityUser(Integer generalSecurityUser) {
        this.generalSecurityUser = generalSecurityUser;
    }

    public String getGeneralCacheId() {
        return generalCacheId;
    }

    public void setGeneralCacheId(String generalCacheId) {
        this.generalCacheId = generalCacheId;
    }

    public Float getGeneralElapsedTime() {
        return generalElapsedTime;
    }

    public void setGeneralElapsedTime(Float generalElapsedTime) {
        this.generalElapsedTime = generalElapsedTime;
    }

    public Integer getGeneralExportPlugin() {
        return generalExportPlugin;
    }

    public void setGeneralExportPlugin(Integer generalExportPlugin) {
        this.generalExportPlugin = generalExportPlugin;
    }

    public Integer getGeneralUa() {
        return generalUa;
    }

    public void setGeneralUa(Integer generalUa) {
        this.generalUa = generalUa;
    }

    public Integer getQueryResultsCount() {
        return queryResultsCount;
    }

    public void setQueryResultsCount(Integer queryResultsCount) {
        this.queryResultsCount = queryResultsCount;
    }

    public String getGeneralCacheHit() {
        return generalCacheHit;
    }

    public void setGeneralCacheHit(String generalCacheHit) {
        this.generalCacheHit = generalCacheHit;
    }

    public Float getLocationScale() {
        return locationScale;
    }

    public void setLocationScale(Float locationScale) {
        this.locationScale = locationScale;
    }

    public Integer getGeneralSessid() {
        return generalSessid;
    }

    public void setGeneralSessid(Integer generalSessid) {
        this.generalSessid = generalSessid;
    }

    public Integer getImagesMainmapWidth() {
        return imagesMainmapWidth;
    }

    public void setImagesMainmapWidth(Integer imagesMainmapWidth) {
        this.imagesMainmapWidth = imagesMainmapWidth;
    }

    public Integer getImagesMainmapSize() {
        return imagesMainmapSize;
    }

    public void setImagesMainmapSize(Integer imagesMainmapSize) {
        this.imagesMainmapSize = imagesMainmapSize;
    }

    public Integer getExportpdfResolution() {
        return exportpdfResolution;
    }

    public void setExportpdfResolution(Integer exportpdfResolution) {
        this.exportpdfResolution = exportpdfResolution;
    }

    public Timestamp getGeneralTime() {
        return generalTime;
    }

    public void setGeneralTime(Timestamp generalTime) {
        this.generalTime = generalTime;
    }

    public double getBboxMinx() {
        return bboxMinx;
    }

    public void setBboxMinx(double bboxMinx) {
        this.bboxMinx = bboxMinx;
    }

    public double getBboxMiny() {
        return bboxMiny;
    }

    public void setBboxMiny(double bboxMiny) {
        this.bboxMiny = bboxMiny;
    }

    public double getBboxMaxx() {
        return bboxMaxx;
    }

    public void setBboxMaxx(double bboxMaxx) {
        this.bboxMaxx = bboxMaxx;
    }

    public double getBboxMaxy() {
        return bboxMaxy;
    }

    public void setBboxMaxy(double bboxMaxy) {
        this.bboxMaxy = bboxMaxy;
    }

    public Integer getLayersSwitchId() {
        return layersSwitchId;
    }

    public void setLayersSwitchId(Integer layersSwitchId) {
        this.layersSwitchId = layersSwitchId;
    }

    public Integer getGeneralClientVersion() {
        return generalClientVersion;
    }

    public void setGeneralClientVersion(Integer generalClientVersion) {
        this.generalClientVersion = generalClientVersion;
    }

    public String getLayers() {
        return layers;
    }

    public void setLayers(String layers) {
        this.layers = layers;
    }

    public StatsRecord clone() throws CloneNotSupportedException {
        return (StatsRecord) super.clone();
    }

    public void setGeneralRequestId(String generalRequestId) {
        this.generalRequestId = generalRequestId;
    }

    public String getGeneralRequestId() {
        return generalRequestId;
    }

    public String getQueryResultsTableCount() {
        return queryResultsTableCount;
    }

    public void setQueryResultsTableCount(String queryResultsTableCount) {
        this.queryResultsTableCount = queryResultsTableCount;
    }

    public void setLayerArray(List<Integer> layerArray) {
        this.layerArray = layerArray;
    }

    public List<Integer> getLayerArray() {
        return layerArray;
    }

    /**
     * @return null if the record is consistent or an error message
     */
    public String isConsistent() {
        if (imagesMainmapWidth != null && imagesMainmapWidth > 500000) {
            return "width too big";
        }
        if (imagesMainmapHeight != null && imagesMainmapHeight > 500000) {
            return "height too big";
        }
        return null;
    }
}
